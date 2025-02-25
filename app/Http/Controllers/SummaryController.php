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
            $breadSales->where(function($query) use ($currentUser) {
                $query->whereIn('company_id', $currentUser->companies->pluck('id'))
                      ->orWhereNull('company_id'); // This allows old bread sales to be visible
            });
        }

        // If admin and specific user selected
        if ($selectedUserId && ($currentUser->isAdmin() || $currentUser->role === 'super_admin')) {
            $user = User::find($selectedUserId);
            $breadSales->whereIn('company_id', $user->companies->pluck('id'));
        } 
        // If admin and no specific user selected (All Users view)
        elseif ($currentUser->isAdmin() || $currentUser->role === 'super_admin') {
            // Get fresh aggregated totals for TODAY only
            $breadSales = BreadSale::whereDate('transaction_date', Carbon::today())
                ->select('bread_type_id')
                ->selectRaw('SUM(CASE WHEN DATE(transaction_date) = ? THEN returned_amount ELSE 0 END) as returned_amount', [$selectedDate])
                ->selectRaw('SUM(CASE WHEN DATE(transaction_date) = ? THEN sold_amount ELSE 0 END) as sold_amount', [$selectedDate])
                ->selectRaw('SUM(CASE WHEN DATE(transaction_date) = ? THEN old_bread_sold ELSE 0 END) as old_bread_sold', [$selectedDate])
                ->selectRaw('SUM(CASE WHEN DATE(transaction_date) = ? THEN returned_amount_1 ELSE 0 END) as returned_amount_1', [$selectedDate])
                ->groupBy('bread_type_id');
        }

        $breadSales = $breadSales->get()->keyBy('bread_type_id');
    
        $breadCounts = $this->calculateBreadCounts($transactions, $selectedDate, $breadSales);
        
        $paymentData = $this->calculateAllPayments(
            $transactions, 
            $breadTypes->pluck('price', 'name')->toArray(),
            $allCompanies
        );

        $transactions = $this->getTransactionsForSummary($selectedDate);
    
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
            // Skip if bread type is missing
            if (!$transaction->breadType) continue;
            
            // Skip if transaction is unpaid for cash companies
            if ($company->type === 'cash' && !$transaction->is_paid) {
                continue;
            }

            // Skip if paid on a different date
            if ($transaction->is_paid && 
                $transaction->paid_date !== null && 
                $transaction->paid_date !== $selectedDate) {
                continue;
            }

            $calculatedTotal = $this->calculateTransactionTotal($transaction, $company, $selectedDate);
            if (is_array($calculatedTotal) && isset($calculatedTotal['netBreads']) && $calculatedTotal['netBreads'] > 0) {
                $breadName = $transaction->breadType->name;
                $payment['breads'][$breadName] = "{$calculatedTotal['netBreads']} x {$calculatedTotal['price']} = " . 
                    number_format($calculatedTotal['total'], 2);
                $payment['total'] += $calculatedTotal['total'];
            }
        }

        if ($payment['total'] > 0) {
            if ($company->type === 'cash') {
                $cashPayments[] = $payment;
                $overallTotal += $payment['total'];
            } else {
                $invoicePayments[] = $payment;
                $overallInvoiceTotal += $payment['total'];
            }
        }
    }

    return [
        'cashPayments' => $cashPayments,
        'invoicePayments' => $invoicePayments,
        'overallTotal' => $overallTotal,
        'overallInvoiceTotal' => $overallInvoiceTotal
    ];
}


private function calculateTransactionTotal($transaction, $company, $date)
{
    if (!$transaction->breadType) {
        return 0;
    }

    $delivered = $transaction->delivered;
    $returned = $transaction->returned;
    $gratis = $transaction->gratis ?? 0;
    $netBreads = $delivered - $returned - $gratis;

    $price = $transaction->breadType->getPriceForCompany($company->id, $date)['price'];
    
    return [
        'netBreads' => $netBreads,
        'price' => $price,
        'total' => $netBreads * $price
    ];
}

// Add a new method to handle old bread sales separately
private function calculateOldBreadSales($date, $userCompanies)
{
    return DailyTransaction::whereDate('transaction_date', $date)
        ->whereIn('company_id', $userCompanies->pluck('id'))
        ->whereNotNull('old_bread_sold')
        ->where('old_bread_sold', '>', 0)
        ->get()
        ->groupBy('bread_type_id');
}

private function getTransactionsForSummary($date)
{
    return DailyTransaction::with(['breadType', 'company'])
        ->where(function ($query) use ($date) {
            $query->where(function ($q) use ($date) {
                // Include transactions from the selected date
                $q->whereDate('transaction_date', $date);
            })->orWhere(function ($q) use ($date) {
                // Include transactions that were paid on the selected date
                $q->where('is_paid', true)
                    ->whereDate('paid_date', $date);
            });
        })
        ->get()
        ->groupBy('company_id');
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
    

    
    public function getAdditionalTableData($date, $selectedUserId = null)
{
    $data = [];
    $totalPrice = 0;

    // Get bread types
    $breadTypes = BreadType::all();
    
    // Get daily transactions for the previous day
    $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
    
    // Query builder for transactions
    $query = DailyTransaction::where('transaction_date', $previousDate);
    if ($selectedUserId) {
        $query->where('user_id', $selectedUserId);
    }
    $previousDayTransactions = $query->get();

    // Query for old bread sales from the current day
    $currentDayQuery = DailyTransaction::where('transaction_date', $date);
    if ($selectedUserId) {
        $currentDayQuery->where('user_id', $selectedUserId);
    }
    $currentDayTransactions = $currentDayQuery->get();

    foreach ($breadTypes as $breadType) {
        $previousDayTransaction = $previousDayTransactions
            ->where('bread_type_id', $breadType->id)
            ->first();

        $currentDayTransaction = $currentDayTransactions
            ->where('bread_type_id', $breadType->id)
            ->first();

        if ($previousDayTransaction) {
            $returned = $previousDayTransaction->returned_amount ?? 0;
            $sold = $currentDayTransaction->old_bread_sold ?? 0; // Get old bread sales
            $returned1 = $previousDayTransaction->returned1 ?? 0;
            $price = $breadType->price;

            // Calculate differences
            $difference = $returned - $sold;
            $difference1 = $difference - $returned1;

            // Calculate total
            $total = $sold * $price;
            $totalPrice += $total;

            $data[$breadType->name] = [
                'returned' => $returned,
                'sold' => $sold,
                'difference' => $difference,
                'returned1' => $returned1,
                'difference1' => $difference1,
                'price' => $price,
                'total' => $total
            ];
        }
    }

    return [
        'data' => $data,
        'totalPrice' => $totalPrice
    ];
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


    public function calculateAdditionalTableData($date, $breadTypes, $breadSales)
{
    $data = [];
    $totalPrice = 0;
    $user = Auth::user();
    $selectedUserId = request('user_id');

    // Get yesterday's date
    $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
    
    // Get all daily transactions for returned bread, regardless of payment status
    $returnedQuery = DailyTransaction::whereDate('transaction_date', $date);
    
    if ($user->role === 'user') {
        $returnedQuery->whereIn('company_id', $user->companies->pluck('id'));
    } elseif (($user->isAdmin() || $user->role === 'super_admin') && $selectedUserId) {
        $selectedUser = User::find($selectedUserId);
        $returnedQuery->whereIn('company_id', $selectedUser->companies->pluck('id'));
    }

    $returnedBread = $returnedQuery->get()->groupBy('bread_type_id');

    // Get old bread sold values, regardless of payment status
    $oldBreadSoldQuery = DailyTransaction::where('transaction_date', $date)
        ->whereNotNull('old_bread_sold');

    if ($user->role === 'user') {
        $oldBreadSoldQuery->whereIn('company_id', $user->companies->pluck('id'));
    } elseif (($user->isAdmin() || $user->role === 'super_admin') && $selectedUserId) {
        $oldBreadSoldQuery->whereIn('company_id', User::find($selectedUserId)->companies->pluck('id'));
    }

    $oldBreadSold = $oldBreadSoldQuery
        ->select('bread_type_id')
        ->selectRaw('SUM(old_bread_sold) as old_bread_sold')
        ->groupBy('bread_type_id')
        ->get()
        ->keyBy('bread_type_id');

    foreach ($breadTypes as $breadType) {
        if (!$breadType->available_for_daily) {
            continue;
        }

        $returned = $returnedBread
            ->get($breadType->id, collect())
            ->sum('returned');
        
        $soldOldBread = $oldBreadSold->get($breadType->id)?->old_bread_sold ?? 0;
        
        $price = $breadType->old_price ?? 0;
        $returned1 = 0;

        $difference = $returned - $soldOldBread;
        $difference1 = $difference - $returned1;
        $total = $soldOldBread * $price;

        $data[$breadType->name] = [
            'returned' => $returned,
            'sold' => $soldOldBread,
            'difference' => $difference,
            'returned1' => $returned1,
            'difference1' => $difference1,
            'price' => $price,
            'total' => $total,
            'user_id' => $user->role === 'user' ? $user->id : null,
            'bread_type_id' => $breadType->id
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
        $selectedUserId = $request->input('selected_user_id');
        
        \Log::info('Update request received', [
            'date' => $date,
            'returned_values' => $returned,
            'sold_values' => $sold
        ]);

        $user = Auth::user();
        
        if ($user->isAdmin() || $user->role === 'super_admin') {
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                if (!$selectedUser) {
                    throw new \Exception('Selected user not found.');
                }
                $company = $selectedUser->companies()->first();
            } else {
                $company = Company::first();
            }
        } else {
            $company = $user->companies()->first();
        }

        \DB::beginTransaction();
        
        // First, delete any existing records for this date and company
        BreadSale::where('transaction_date', $date)
            ->where('company_id', $company->id)
            ->delete();
            
        \Log::info('Deleted existing records for date and company', [
            'date' => $date,
            'company_id' => $company->id
        ]);

        foreach ($returned as $breadName => $returnedAmount) {
            $breadType = BreadType::where('name', $breadName)->first();
            
            if (!$breadType) {
                \Log::warning("Bread type not found: {$breadName}");
                continue;
            }
            
            // Convert to integer, allowing zero values
            $returnedAmount = $returnedAmount !== '' ? (int)$returnedAmount : 0;
            $soldAmount = isset($sold[$breadName]) && $sold[$breadName] !== '' ? (int)$sold[$breadName] : 0;
            
            // Create new record
            $breadSale = new BreadSale([
                'bread_type_id' => $breadType->id,
                'transaction_date' => $date,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'returned_amount' => $returnedAmount,
                'sold_amount' => $soldAmount,
                'total_amount' => $soldAmount * $breadType->price,
                'old_bread_sold' => 0,
                'returned_amount_1' => 0
            ]);
            
            $breadSale->save();
            
            \Log::info("Created new record for {$breadName}", [
                'record_id' => $breadSale->id,
                'returned_amount' => $returnedAmount,
                'sold_amount' => $soldAmount
            ]);
        }
        
        \DB::commit();
        
        \Log::info('Transaction committed successfully');

        return redirect()
            ->back()
            ->with('success', 'Успешно ажурирање на табелата');
            
    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Error updating bread sales: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()
            ->back()
            ->with('error', 'Error updating data: ' . $e->getMessage());
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

    public function markMultipleAsPaid(Request $request)
{
    try {
        $selectedTransactions = $request->input('selected_transactions', []);
        
        DB::beginTransaction();
        
        foreach ($selectedTransactions as $transaction) {
            list($companyId, $date) = explode('_', $transaction);
            
            DailyTransaction::where('company_id', $companyId)
                ->whereDate('transaction_date', $date)
                ->where('is_paid', false)
                ->update([
                    'is_paid' => true,
                    'paid_date' => now(),
                    'updated_at' => now()
                ]);
        }
        
        DB::commit();
        
        return back()
            ->with('success', 'Избраните трансакции се успешно означени како платени.')
            ->with('scroll', session('scroll_position'));
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error marking multiple transactions as paid: ' . $e->getMessage());
        return back()->with('error', 'Се појави грешка при означување на трансакциите како платени.');
    }
}

    public function showAdditionalTable(Request $request)
    {
        $date = $request->input('date', Carbon::yesterday()->toDateString());
        $currentUser = Auth::user();
        $selectedUserId = $request->input('selected_user_id');

        // Determine the companies to show based on user role
        if ($currentUser->isAdmin() || $currentUser->role === 'super_admin') {
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                $companies = $selectedUser->companies;
            } else {
                $companies = Company::all();
            }
        } else {
            $companies = $currentUser->companies;
        }

        // Get bread sales data
        $breadSales = BreadSale::whereDate('transaction_date', $date)
            ->whereIn('company_id', $companies->pluck('id'))
            ->get()
            ->keyBy('bread_type_id');

        // Get daily transactions
        $dailyTransactions = DailyTransaction::with('breadType')
            ->whereNotNull('bread_type_id')
            ->whereHas('breadType')
            ->whereDate('transaction_date', $date)
            ->whereIn('company_id', $companies->pluck('id'))
            ->get()
            ->groupBy('bread_type_id');

        $additionalTableData = [];
        $totalPrice = 0;

        // Get all bread types that are available for daily
        $breadTypes = BreadType::where('available_for_daily', true)->get();

        foreach ($breadTypes as $breadType) {
            $transactions = $dailyTransactions->get($breadType->id, collect());
            $breadSale = $breadSales->get($breadType->id);
            
            $returnedToday = $transactions->sum('returned');
            $soldOldBread = $breadSale ? $breadSale->old_bread_sold : 0;
            $returned1 = $breadSale ? $breadSale->returned_amount_1 : 0;
            $price = $breadType->old_price;

            $difference = $returnedToday - $soldOldBread;
            $difference1 = $difference - $returned1;
            $total = $soldOldBread * $price;

            $additionalTableData[$breadType->name] = [
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

        return view('daily-transactions.index', [
            'additionalTableData' => [
                'data' => $additionalTableData,
                'totalPrice' => $totalPrice,
            ],
            'date' => $date,
            'selectedUserId' => $selectedUserId,
            'currentUser' => $currentUser
        ]);
    }
}