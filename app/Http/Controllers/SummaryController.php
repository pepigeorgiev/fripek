<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\BreadType;
use App\Models\DailyTransaction;
use App\Models\BreadSale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
    
        // Get all users for admin dropdown (excluding super admin)
        $users = User::where('role', '!=', 'super_admin')
                    ->orderBy('name')
                    ->get();
        
        // Get selected user ID from request or default to current user
        $selectedUserId = $request->get('user_id');
        
        // Determine which companies to show based on user role and selection
        if ($currentUser->isAdmin() || $currentUser->role === 'super_admin') {
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                $allCompanies = $selectedUser->companies;
                $company = $selectedUser->companies()->first();
            } else {
                // If no user selected, show all companies
                $allCompanies = Company::all();
                $company = $allCompanies->first();
            }
        } else {
            $allCompanies = $currentUser->companies;
            $company = $currentUser->companies()->first();
        }
        
        if ($allCompanies->isEmpty()) {
            return redirect()->back()->with('error', 'Нема компанија поврзана со вашиот акаунт.');
        }

   
    
        // Handle date selection
        $selectedDate = $request->input('date', now()->toDateString());
    
        // Get unique dates from transactions for the date picker
        $availableDates = DailyTransaction::whereIn('company_id', $allCompanies->pluck('id'))
            ->select('transaction_date')
            ->distinct()
            ->orderBy('transaction_date', 'desc')
            ->pluck('transaction_date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->push(now()->format('Y-m-d'))
            ->unique()
            ->sortDesc()
            ->values();
    
        $breadTypes = BreadType::where(function($query) use ($selectedDate) {
            $query->where('is_active', true)
                  ->orWhereHas('dailyTransactions', function($q) use ($selectedDate) {
                      $q->whereDate('transaction_date', $selectedDate);
                  });
        })->get();
    
        $companyIds = $allCompanies->pluck('id')->toArray();
    
        // Get transactions for the selected date

        $selectedDate = $request->input('date', now()->toDateString());
    
        $query = DailyTransaction::with(['breadType', 'company'])
            ->whereNotNull('bread_type_id')
            ->whereHas('breadType')
            ->whereDate('transaction_date', $selectedDate)
            ->whereIn('company_id', $companyIds);
    
        $transactions = $query->get()->groupBy('company_id');
    
        // Get paid transactions for the selected date
        $paidTransactionsQuery = DailyTransaction::with(['breadType', 'company'])
            ->whereNotNull('bread_type_id')
            ->whereHas('breadType')
            ->where('is_paid', true)
            ->whereDate('paid_date', $selectedDate)
            ->whereIn('company_id', $companyIds);
    
        $paidTransactions = $paidTransactionsQuery->get()->groupBy('company_id');
       
    
        // Get bread sales for the selected date
        $breadSales = BreadSale::whereDate('transaction_date', $selectedDate);

        // If regular user, show only their data
        if (!$currentUser->isAdmin() && $currentUser->role !== 'super_admin') {
            $breadSales->whereIn('company_id', $currentUser->companies->pluck('id'));
        }

        // If admin and specific user selected
        if ($selectedUserId && ($currentUser->isAdmin() || $currentUser->role === 'super_admin')) {
            $user = User::find($selectedUserId);
            $breadSales->whereIn('company_id', $user->companies->pluck('id'));
        } 
        // If admin and no specific user selected (All Users view)
        elseif ($currentUser->isAdmin() || $currentUser->role === 'super_admin') {
            // Get the latest records for each company and bread type
            $latestIds = BreadSale::whereDate('transaction_date', $selectedDate)
                ->select(DB::raw('MAX(id) as id'))
                ->groupBy('company_id', 'bread_type_id')
                ->pluck('id');

            $breadSales = BreadSale::whereIn('id', $latestIds)
                ->select('bread_type_id')
                ->selectRaw('SUM(returned_amount) as returned_amount')
                ->selectRaw('SUM(sold_amount) as sold_amount')
                ->selectRaw('SUM(old_bread_sold) as old_bread_sold')
                ->selectRaw('SUM(returned_amount_1) as returned_amount_1')
                ->groupBy('bread_type_id');

            Log::info('All users query', [
                'sql' => $breadSales->toSql(),
                'bindings' => $breadSales->getBindings(),
                'latestIds' => $latestIds
            ]);
        }

        $results = $breadSales->get();
        Log::info('Query results:', [
            'count' => $results->count(),
            'data' => $results->toArray()
        ]);

        $breadSales = $results->keyBy('bread_type_id');
    
        $breadCounts = $this->calculateBreadCounts($transactions, $selectedDate, $breadSales);
        
        $paymentData = $this->calculateAllPayments(
            $transactions, 
            $breadTypes->pluck('price', 'name')->toArray(),
            $allCompanies
        );
    
        // Get unpaid transactions separately
        $unpaidTransactions = $this->getUnpaidTransactions($selectedDate, $allCompanies);
        
        $totals = $this->calculateTotals($breadCounts, $breadTypes);
        
        $additionalTableData = $this->calculateAdditionalTableData($selectedDate, $breadTypes, $breadSales);

        $todayBreadTotal = $totals['totalInPrice'];
    $yesterdayBreadTotal = $additionalTableData['totalPrice'];
    $breadSalesTotal = $todayBreadTotal + $yesterdayBreadTotal;
    
        return view('summary', [
            'breadTypes' => $breadTypes,
            'breadCounts' => $breadCounts,
            'cashPayments' => $paymentData['cashPayments'],
            'invoicePayments' => $paymentData['invoicePayments'],
            'overallTotal' => $paymentData['overallTotal'],
            'overallInvoiceTotal' => $paymentData['overallInvoiceTotal'],
            'totalSold' => $totals['totalSold'],
            'totalInPrice' => $totals['totalInPrice'],
            'date' => $selectedDate,
            'availableDates' => $availableDates,
            'additionalTableData' => $additionalTableData,
            'breadSales' => $breadSales,
            'company' => $company,
            'unpaidTransactions' => $unpaidTransactions, // Use only this for unpaid transactions
            'todayBreadTotal' => $todayBreadTotal,
            'yesterdayBreadTotal' => $yesterdayBreadTotal,
            'breadSalesTotal' => $breadSalesTotal,
            'totalCashRevenue' => $breadSalesTotal + $paymentData['overallTotal'],
            'paidTransactions' => $paidTransactions,
            'users' => $users,
            'selectedUserId' => $selectedUserId,
            'currentUser' => $currentUser

        ]);
    }
    


    
    

    private function calculateBreadCounts($transactions, $date, $breadSales)
{
    $counts = [];
    $allBreadTypes = BreadType::where('is_active', true)->get();
    
    // Initialize counts for all bread types
    foreach ($allBreadTypes as $breadType) {
        // Only use bread sales data if it exists for this specific bread type
        $breadSale = $breadSales->get($breadType->id);
        
        $counts[$breadType->name] = [
            'sent' => 0,
            'returned' => $breadSale ? $breadSale->returned_amount : 0,
            'sold' => $breadSale ? $breadSale->sold_amount : 0,
            'price' => $breadType->price,
            'total_price' => 0
        ];
    }
    
    // Add transaction data if exists
    if (!empty($transactions)) {
        foreach ($transactions as $companyTransactions) {
            foreach ($companyTransactions as $transaction) {
                $breadType = $transaction->breadType;
                if (!$breadType) continue;
                
                $breadTypeName = $breadType->name;
                if (isset($counts[$breadTypeName])) {
                    $counts[$breadTypeName]['sent'] += $transaction->delivered;
                }
            }
        }
    }
    
    // Calculate totals using the correct data
    foreach ($counts as $breadTypeName => &$count) {
        $count['total_price'] = $count['sold'] * $count['price'];
    }
    
    return $counts;
}



    private function calculateAllPayments($transactions, $breadPrices, $userCompanies)
    {
        $cashPayments = [];
        $invoicePayments = [];
        $overallTotal = 0;
        $overallInvoiceTotal = 0;
        $selectedDate = request('date', now()->toDateString());
    
        // Process current date transactions
        foreach ($transactions as $companyId => $companyTransactions) {
            $company = $userCompanies->firstWhere('id', $companyId);
            if (!$company) continue;
    
            $payment = [
                'company' => $company->name,
                'company_id' => $companyId,
                'breads' => [],
                'total' => 0
            ];
    
            foreach ($companyTransactions as $transaction) {
                if (!$transaction->breadType) continue;
    
                // Skip unpaid transactions for cash companies
                if ($company->type === 'cash' && !$transaction->is_paid) {
                    continue;
                }
    
                $breadType = $transaction->breadType;
                // For transactions paid today, use today's price
                $prices = $breadType->getPriceForCompany($companyId, $selectedDate);
                
                $delivered = $transaction->delivered;
                $returned = $transaction->returned;
                $gratis = $transaction->gratis ?? 0;
                $netBreads = $delivered - $returned - $gratis;
                $total = $netBreads * $prices['price'];
    
                // Skip if paid on a different date
                if ($transaction->is_paid && 
                    $transaction->paid_date !== null && 
                    $transaction->paid_date !== $selectedDate) {
                    continue;
                }
    
                $payment['breads'][$breadType->name] = "{$netBreads} x {$prices['price']} = " . number_format($total, 2);
                $payment['total'] += $total;
            }
    
            if ($company->type === 'cash') {
                if ($payment['total'] > 0) {
                    $cashPayments[] = $payment;
                    $overallTotal += $payment['total'];
                }
            } else {
                $invoicePayments[] = $payment;
                $overallInvoiceTotal += $payment['total'];
            }
        }
    
        // Handle paid transactions from earlier dates
        $paidToday = DailyTransaction::with(['breadType', 'company'])
            ->whereNotNull('bread_type_id')
            ->whereHas('breadType')
            ->whereIn('company_id', $userCompanies->pluck('id'))
            ->where('is_paid', true)
            ->whereDate('paid_date', $selectedDate)
            ->whereDate('transaction_date', '<', $selectedDate)
            ->get();
    
        foreach ($paidToday->groupBy('company_id') as $companyId => $companyTransactions) {
            $company = $userCompanies->firstWhere('id', $companyId);
            if (!$company || $company->type !== 'cash') continue;
    
            $payment = [
                'company' => $company->name,
                'company_id' => $companyId,
                'breads' => [],
                'total' => 0
            ];
    
            foreach ($companyTransactions as $transaction) {
                if (!$transaction->breadType) continue;
    
                $breadType = $transaction->breadType;
                // Use TODAY'S price (paid_date price) for the calculation
                $prices = $breadType->getPriceForCompany($companyId, $selectedDate);
                
                // But use original transaction quantities
                $delivered = $transaction->delivered;
                $returned = $transaction->returned;
                $gratis = $transaction->gratis ?? 0;
                $netBreads = $delivered - $returned - $gratis;
                $total = $netBreads * $prices['price'];
    
                $payment['breads'][$breadType->name] = "{$netBreads} x {$prices['price']} = " . number_format($total, 2);
                $payment['total'] += $total;
            }
    
            if ($payment['total'] > 0) {
                $cashPayments[] = $payment;
                $overallTotal += $payment['total'];
            }
        }
    
        return [
            'cashPayments' => $cashPayments,
            'invoicePayments' => $invoicePayments,
            'overallTotal' => $overallTotal,
            'overallInvoiceTotal' => $overallInvoiceTotal
        ];
    }


    private function getPaidTransactionsForDate($date, $userCompanies)
    {
        return DailyTransaction::with(['breadType', 'company'])
            ->whereNotNull('bread_type_id')
            ->whereHas('breadType')
            ->whereHas('company', function($query) {
                $query->where('type', 'cash');
            })
            ->whereIn('company_id', $userCompanies->pluck('id'))
            ->where('is_paid', true)
            ->whereDate('paid_date', $date)
            ->get()
            ->groupBy('company_id');
    }
    

    

    private function calculateAdditionalData($date, $breadCounts, $prices)
    {
        $additionalData = [];
        $totalSold = 0;
        $totalInPrice = 0;

        foreach ($breadCounts as $breadType => $counts) {
            $sold = $counts['total'];
            $price = $prices[$breadType] ?? 0;
            $totalForType = $sold * $price;

            $additionalData[] = [
                'breadType' => $breadType,
                'sold' => $sold,
                'price' => $price,
                'total' => $totalForType
            ];

            $totalSold += $sold;
            $totalInPrice += $totalForType;
        }

        return [$additionalData, $totalSold, $totalInPrice];
    }


    private function calculateAdditionalTableData($date, $breadTypes, $breadSales)
{
    $data = [];
    $totalPrice = 0;
    $user = Auth::user();
    
    // Get the appropriate company based on user role
    if ($user->isAdmin() || $user->role === 'super_admin') {
        $selectedUserId = request('user_id');
        if ($selectedUserId) {
            $selectedUser = User::find($selectedUserId);
            $companies = $selectedUser->companies;
        } else {
            $companies = Company::all();
        }
    } else {
        $companies = $user->companies;
    }

    $dailyTransactions = DailyTransaction::with('breadType')
        ->whereNotNull('bread_type_id')
        ->whereHas('breadType')
        ->whereDate('transaction_date', $date)
        ->whereIn('company_id', $companies->pluck('id'))
        ->get()
        ->groupBy('bread_type_id');

    foreach ($breadTypes as $breadType) {
        if (!$breadType->available_for_daily) {
            continue;
        }

        $breadSale = $breadSales->get($breadType->id);
        
        $returnedToday = 0;
        if (isset($dailyTransactions[$breadType->id])) {
            $returnedToday = $dailyTransactions[$breadType->id]->sum('returned');
        }

        $soldOldBread = $breadSale ? $breadSale->old_bread_sold : 0;
        $returned1 = $breadSale ? $breadSale->returned_amount_1 : 0;
        $price = $breadType->old_price;

        $difference = $returnedToday - $soldOldBread;
        $difference1 = $difference - $returned1;
        $total = $soldOldBread * $price;

        $data[$breadType->name] = [
            'returned' => $returnedToday,
            'sold' => $soldOldBread,
            'difference' => $difference,
            'returned1' => $returned1,
            'difference1' => $difference1,
            'price' => $price,
            'total' => $total
        ];

        $totalPrice += $total;
    }

    return [
        'data' => $data,
        'totalPrice' => $totalPrice
    ];
}


    
public function update(Request $request)
{
    try {
        $date = $request->input('date');
        $returned = $request->input('returned', []);
        $sold = $request->input('sold', []);
        $selectedUserId = $request->input('selected_user_id'); // New parameter
        
        $user = Auth::user();
        
        // Get the appropriate company based on user role
        if ($user->isAdmin() || $user->role === 'super_admin') {
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                if (!$selectedUser) {
                    throw new \Exception('Selected user not found.');
                }
                $company = $selectedUser->companies()->first();
                $effectiveUser = $selectedUser; // Use selected user for bread sales
            } else {
                // If no user selected, use the first company
                $company = Company::first();
                $effectiveUser = $user;
            }
        } else {
            $company = $user->companies()->first();
            $effectiveUser = $user;
        }



        
        \DB::beginTransaction();
        
        // Get existing bread sales for this date
        $existingBreadSales = BreadSale::where('transaction_date', $date)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('bread_type_id');
        
        foreach ($returned as $breadName => $returnedAmount) {
            $breadType = BreadType::where('name', $breadName)->first();
            
            if (!$breadType) {
                Log::warning("Bread type not found: {$breadName}");
                continue;
            }
            
            $returnedAmount = !empty($returnedAmount) ? (int)$returnedAmount : 0;
            $soldAmount = !empty($sold[$breadName]) ? (int)$sold[$breadName] : 0;
            
            // Get existing record if any
            $existingRecord = $existingBreadSales->get($breadType->id);
            
            // Prepare update data while preserving old_bread_sold
            $updateData = [
                'returned_amount' => $returnedAmount,
                'sold_amount' => $soldAmount,
                'total_amount' => $soldAmount * $breadType->price,
            ];
            
            // If there's an existing record, preserve the old_bread_sold value
            if ($existingRecord) {
                $updateData['old_bread_sold'] = $existingRecord->old_bread_sold;
            }
            
            // Update or create the record
            BreadSale::updateOrCreate(
                [
                    'bread_type_id' => $breadType->id,
                    'transaction_date' => $date,
                    'company_id' => $company->id,
                    'user_id' => $user->id
                ],
                $updateData
            );
        }
        
        \DB::commit();
        
        return redirect()
            ->back()
            ->with('success', 'Успешно ажурирање на табелата');
            
    } catch (\Exception $e) {
        \DB::rollBack();
        Log::error('Error updating bread sales: ' . $e->getMessage());
        
        return redirect()
            ->back()
            ->with('error', 'Error updating data: ' . $e->getMessage());
    }
}

public function updateAdditional(Request $request)
{
    try {
        $date = $request->input('date');
        $sold = $request->input('sold', []);
        $returned1 = $request->input('returned1', []);
        $selectedUserId = $request->input('selected_user_id'); // New parameter
        
        $user = Auth::user();
        
        // Get the appropriate company based on user role
        if ($user->isAdmin() || $user->role === 'super_admin') {
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                if (!$selectedUser) {
                    throw new \Exception('Selected user not found.');
                }
                $company = $selectedUser->companies()->first();
                $effectiveUser = $selectedUser; // Use selected user for bread sales
            } else {
                // If no user selected, use the first company
                $company = Company::first();
                $effectiveUser = $user;
            }
        } else {
            $company = $user->companies()->first();
            $effectiveUser = $user;
        }
        
        if (!$company) {
            throw new \Exception('No company associated with the user.');
        }
        
        \DB::beginTransaction();
        
        $existingBreadSales = BreadSale::where('transaction_date', $date)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('bread_type_id');

        // Process all bread types that have either sold or returned1 values
        $breadNames = array_unique(array_merge(array_keys($sold), array_keys($returned1)));
        
        foreach ($breadNames as $breadName) {
            $breadType = BreadType::where('name', $breadName)
                ->where('available_for_daily', true)
                ->first();

            if (!$breadType) {
                Log::warning("Bread type not found or not available for daily: {$breadName}");
                continue;
            }

            $soldAmount = !empty($sold[$breadName]) ? (int)$sold[$breadName] : 0;
            $returned1Amount = !empty($returned1[$breadName]) ? (int)$returned1[$breadName] : 0;
            
            $existingRecord = $existingBreadSales->get($breadType->id);
            
            $updateData = [
                'old_bread_sold' => $soldAmount,
                'returned_amount_1' => $returned1Amount
            ];
            
            if ($existingRecord) {
                $updateData['returned_amount'] = $existingRecord->returned_amount;
                $updateData['sold_amount'] = $existingRecord->sold_amount;
                $updateData['total_amount'] = $existingRecord->total_amount;
            }

            BreadSale::updateOrCreate(
                [
                    'bread_type_id' => $breadType->id,
                    'transaction_date' => $date,
                    'user_id' => $effectiveUser->id, // Changed from $user->id to $effectiveUser->id
                    'company_id' => $company->id
                ],
                $updateData
            );
        }
        
        \DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Успешно ажурирање на табелата');
    } catch (\Exception $e) {
        \DB::rollBack();
        Log::error('Error updating old bread sales: ' . $e->getMessage());
        
        return redirect()
            ->back()
            ->with('error', 'Error updating old bread sales: ' . $e->getMessage());
    }
}


    private function getUnpaidTransactions($selectedDate, $companies)
    {
        try {
            Log::info('Fetching unpaid transactions', [
                'date' => $selectedDate,
                'companies' => $companies->pluck('name', 'id')
            ]);

            $unpaidTransactions = DailyTransaction::with(['breadType', 'company'])
                ->whereNotNull('bread_type_id')
                ->whereHas('breadType')
                ->where('is_paid', false)
                ->whereHas('company', function($query) {
                    $query->where('type', 'cash');
                })
                ->whereIn('company_id', $companies->pluck('id'))
                ->orderBy('transaction_date', 'desc')
                ->get();

            Log::info('Found unpaid transactions', [
                'count' => $unpaidTransactions->count(),
                'transactions' => $unpaidTransactions->map(fn($t) => [
                    'id' => $t->id,
                    'company' => $t->company->name,
                    'date' => $t->transaction_date,
                    'is_paid' => $t->is_paid
                ])
            ]);

            $result = [];
            
            foreach ($unpaidTransactions->groupBy(['company_id', 'transaction_date']) as $companyId => $dateGroups) {
                foreach ($dateGroups as $date => $transactions) {
                    $company = $companies->firstWhere('id', $companyId);
                    if (!$company) continue;

                    $payment = [
                        'company' => $company->name,
                        'company_id' => $companyId,
                        'transaction_date' => $date,
                        'breads' => []
                    ];

                    $totalAmount = 0;
                    foreach ($transactions as $transaction) {
                        if ($transaction->breadType) {
                            $delivered = $transaction->delivered;
                            $returned = $transaction->returned;
                            $gratis = $transaction->gratis ?? 0;
                            
                            $prices = $transaction->breadType->getPriceForCompany($companyId, $date);
                            $price = $prices['price'];
                            
                            $netBreads = $delivered - $returned - $gratis;
                            $totalForType = $netBreads * $price;
                            
                            $payment['breads'][$transaction->breadType->name] = [
                                'delivered' => $delivered,
                                'returned' => $returned,
                                'gratis' => $gratis,
                                'total' => $netBreads,
                                'price' => $price,
                                'potential_total' => $totalForType
                            ];
                            
                            $totalAmount += $totalForType;
                        }
                    }
                    
                    $payment['total_amount'] = $totalAmount;
                    $result[] = $payment;
                }
            }

            Log::info('Processed unpaid transactions', [
                'result_count' => count($result)
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting unpaid transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

   

    private function calculateTotals($breadCounts, $breadTypes)
    {
        $totalSold = 0;
        $totalInPrice = 0;

        foreach ($breadCounts as $breadType => $counts) {
            $totalSold += $counts['sold'];
            $totalInPrice += $counts['total_price'];
        }

        return [
            'totalSold' => $totalSold,
            'totalInPrice' => $totalInPrice
        ];
    }
}