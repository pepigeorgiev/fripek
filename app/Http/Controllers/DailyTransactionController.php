<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\BreadType;
use App\Models\DailyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\TracksHistory;
use App\Models\TransactionHistory;


class DailyTransactionController extends Controller
{
    use TracksHistory;

    public function index()
    {
        $user = Auth::user();
        $companies = $user->isAdmin() ? Company::all() : $user->companies;
        
        $today = now()->toDateString();
        
        // Get today's transactions for active bread types only
        $todaysTransactions = DailyTransaction::with(['company', 'breadType' => function($query) {
                $query->where('is_active', true);
            }])
            ->whereIn('company_id', $companies->pluck('id'))
            ->whereDate('transaction_date', $today)
            ->whereHas('breadType', function($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->groupBy('company_id');

        return view('daily-transactions.index', compact('companies', 'todaysTransactions'));
    }
    

    // Add this to your DailyTransactionController.php class, replacing or updating the create method:

public function create()
{
    $user = Auth::user();
    $companies = $user->isAdmin() ? Company::all() : $user->companies;
    
    // Get date and company_id from request parameters
    $date = request('date', now()->toDateString());
    $selectedCompanyId = request('company_id');
    

    
    // Start with all active bread types
    $breadTypes = BreadType::where('is_active', true)->get();
    
    // If a company is selected, filter the bread types
    if ($selectedCompanyId) {
        $selectedCompany = Company::find($selectedCompanyId);
        
        if ($selectedCompany) {
            
            // Get bread types associated with this company
            $companyBreadTypes = $selectedCompany->breadTypes()->pluck('bread_types.id')->toArray();
            
            
            // If company has specific bread types, filter to show only those
            if (!empty($companyBreadTypes)) {
                $breadTypes = $breadTypes->whereIn('id', $companyBreadTypes)->values();
            }
        }
    }
    
    $existingTransactions = DailyTransaction::whereIn('company_id', $companies->pluck('id'))
        ->whereDate('transaction_date', $date)
        ->whereHas('breadType', function($query) {
            $query->where('is_active', true);
        })
        ->get()
        ->groupBy('company_id');
        
    return view('daily-transactions.create', compact(
        'companies',
        'breadTypes',
        'date',
        'existingTransactions',
        'selectedCompanyId'
    ));
}
    // public function create()
    // {
    //     $user = Auth::user();
    //     $companies = $user->isAdmin() ? Company::all() : $user->companies;
    //     $breadTypes = BreadType::where('is_active', true)->get();
        
    //     // Get date and company_id from request
    //     $date = request('date', now()->toDateString());
    //     $selectedCompanyId = request('company_id');
        
    //     $existingTransactions = DailyTransaction::whereIn('company_id', $companies->pluck('id'))
    //         ->whereDate('transaction_date', $date)
    //         ->whereHas('breadType', function($query) {
    //             $query->where('is_active', true);
    //         })
    //         ->get()
    //         ->groupBy('company_id');
            
    //     return view('daily-transactions.create', compact(
    //         'companies',
    //         'breadTypes',
    //         'date',
    //         'existingTransactions',
    //         'selectedCompanyId'
    //     ));
    // }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'transaction_date' => 'required|date',
            'transactions' => 'required|array',
            'transactions.*.bread_type_id' => ['required', 'exists:bread_types,id'],
            'transactions.*.delivered' => 'required|integer|min:0',
            'transactions.*.returned' => 'required|integer|min:0',
            'transactions.*.gratis' => 'required|integer|min:0',
        ]);
        
   
        
        try {
            DB::transaction(function () use ($validatedData, $request) {
                $company = Company::find($validatedData['company_id']);
                $isPaid = !$request->has('is_paid');
                $shouldTrackPayment = $company->type === 'cash';
                
         
                
                // Get existing transactions for history
                $existingTransactions = DailyTransaction::where([
                    'company_id' => $validatedData['company_id'],
                    'transaction_date' => $validatedData['transaction_date']
                ])->get()->keyBy('bread_type_id');

                foreach ($validatedData['transactions'] as $transaction) {
                    $breadType = BreadType::find($transaction['bread_type_id']);
                    if ($breadType && $breadType->is_active) {
                        $transactionIsPaid = $shouldTrackPayment ? $isPaid : true;
                        $paidDate = $transactionIsPaid ? now()->toDateString() : null;
                
                        // Calculate the price based on company's price group
                        $price = DailyTransaction::calculatePriceForBreadType(
                            $breadType, 
                            $company, 
                            $validatedData['transaction_date']
                        );
                        
                 
                
                        // Create/update the transaction with the calculated price
                        $newTransaction = DailyTransaction::updateOrCreate(
                            [
                                'company_id' => $validatedData['company_id'],
                                'transaction_date' => $validatedData['transaction_date'],
                                'bread_type_id' => $transaction['bread_type_id']
                            ],
                            [
                                'delivered' => $transaction['delivered'],
                                'returned' => $transaction['returned'],
                                'gratis' => $transaction['gratis'],
                                'is_paid' => $transactionIsPaid,
                                'paid_date' => $paidDate,
                                'price' => $price // Make sure to save the price
                            ]
                        );

                // foreach ($validatedData['transactions'] as $transaction) {
                //     $breadType = BreadType::find($transaction['bread_type_id']);
                //     if ($breadType && $breadType->is_active) {
                //         $transactionIsPaid = $shouldTrackPayment ? $isPaid : true;
                //         $paidDate = $transactionIsPaid ? now()->toDateString() : null;

                //         // Calculate the price based on company's price group
                //         $price = DailyTransaction::calculatePriceForBreadType(
                //             $breadType, 
                //             $company, 
                //             $validatedData['transaction_date']
                //         );
                        
                //         // Log the calculated price
                //         Log::info('Price calculated for transaction', [
                //             'bread_type' => $breadType->name,
                //             'company_price_group' => $company->price_group,
                //             'calculated_price' => $price
                //         ]);

                        // Create/update the transaction with the calculated price
                        $newTransaction = DailyTransaction::updateOrCreate(
                            [
                                'company_id' => $validatedData['company_id'],
                                'transaction_date' => $validatedData['transaction_date'],
                                'bread_type_id' => $transaction['bread_type_id']
                            ],
                            [
                                'delivered' => $transaction['delivered'],
                                'returned' => $transaction['returned'],
                                'gratis' => $transaction['gratis'],
                                'is_paid' => $transactionIsPaid,
                                'paid_date' => $paidDate
                                //  'price' => $price // Set the calculated price
                            ]
                        );

                        // Record history if there was an existing transaction
                        if (isset($existingTransactions[$transaction['bread_type_id']])) {
                            $oldTransaction = $existingTransactions[$transaction['bread_type_id']];
                            $this->recordHistory($newTransaction, [
                                'delivered' => $oldTransaction->delivered,
                                'returned' => $oldTransaction->returned,
                                'gratis' => $oldTransaction->gratis,
                                'price' => $oldTransaction->price
                            ], [
                                'delivered' => $transaction['delivered'],
                                'returned' => $transaction['returned'],
                                'gratis' => $transaction['gratis'],
                                // 'price' => $price
                            ]);
                        }
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Успешно ажурирање на дневни трансакции.'
            ]);
                    
        } catch (\Exception $e) {
            Log::error('Error storing daily transactions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Грешка при зачувување на трансакциите.'
            ], 500);
        }
    }


public function storeOldBreadSales(Request $request)
{


    $request->validate([
        'transaction_date' => 'required|date',
        'old_bread_sold.*.bread_type_id' => 'required|exists:bread_types,id',
        'old_bread_sold.*.sold' => 'required|integer|min:0',
    ]);

    try {
        DB::beginTransaction();

        $user = Auth::user();
        $company = $user->companies()->first();
        $date = $request->input('transaction_date');
        $oldBreadData = $request->input('old_bread_sold', []);

        foreach ($oldBreadData as $breadTypeId => $data) {
            if (empty($data['sold'])) continue;

            // Get existing transaction for this date and bread type
            $transaction = DailyTransaction::firstOrNew([
                'company_id' => $company->id,
                'bread_type_id' => $data['bread_type_id'],
                'transaction_date' => $date
            ]);

            // Add new amount to existing old_bread_sold (or 0 if new record)
            $currentAmount = $transaction->old_bread_sold ?? 0;
            $newAmount = $currentAmount + intval($data['sold']);

      

            $transaction->old_bread_sold = $newAmount;
            $transaction->save();
        }

        DB::commit();
        return redirect()->back()->with('success', 'Успешно ажурирање на стар леб');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error in storeOldBreadSales: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Грешка при зачувување');
    }
}


private function calculateTotalForTransaction($transaction, $company, $date)
{
    if (!$transaction->breadType) {
        return [
            'netBreads' => 0,
            'price' => 0,
            'total' => 0,
            'old_bread_sold' => 0
        ];
    }

    $delivered = $transaction->delivered;
    $returned = $transaction->returned;
    $gratis = $transaction->gratis ?? 0;
    $oldBreadSold = $transaction->old_bread_sold ?? 0;
    $netBreads = $delivered - $returned - $gratis;

    // Use the standardized price calculation method
    $price = DailyTransaction::calculatePriceForBreadType(
        $transaction->breadType, 
        $company, 
        $date
    );
    
    $oldBreadPrice = $transaction->breadType->old_price ?? $price;
    
    return [
        'netBreads' => $netBreads,
        'price' => $price,
        'total' => $netBreads * $price,
        'old_bread_sold' => $oldBreadSold,
        'old_bread_total' => $oldBreadSold * $oldBreadPrice
    ];
}

// private function calculateTotalForTransaction($transaction, $company, $date)
// {
//     if (!$transaction->breadType) {
//         return [
//             'netBreads' => 0,
//             'price' => 0,
//             'total' => 0,
//             'old_bread_sold' => 0
//         ];
//     }

//     $delivered = $transaction->delivered;
//     $returned = $transaction->returned;
//     $gratis = $transaction->gratis ?? 0;
//     $oldBreadSold = $transaction->old_bread_sold ?? 0;
//     $netBreads = $delivered - $returned - $gratis;

//     // Use our standardized price calculation method
//     $price = DailyTransaction::calculatePriceForBreadType(
//         $transaction->breadType, 
//         $company, 
//         $date
//     );
    
//     $oldBreadPrice = $transaction->breadType->old_price ?? $price;
    
//     return [
//         'netBreads' => $netBreads,
//         'price' => $price,
//         'total' => $netBreads * $price,
//         'old_bread_sold' => $oldBreadSold,
//         'old_bread_total' => $oldBreadSold * $oldBreadPrice
//     ];
// }





public function markAsPaid(Request $request)
{
    try {
        DB::transaction(function () use ($request) {
            $date = $request->input('date');
            $companyId = $request->input('company_id');
            
            // Fetch unpaid transactions for the specific company and date
            $transactions = DailyTransaction::where([
                'company_id' => $companyId,
                'transaction_date' => $date,
                'is_paid' => false
            ])->get();

            foreach ($transactions as $transaction) {
                // Store original values
                $originalValues = $transaction->only(['delivered', 'returned', 'gratis']);

                // Update transaction
                $transaction->is_paid = true;
                $transaction->paid_date = now()->toDateString();
                $transaction->save();

                // Create history record
                // $this->createHistoryRecord($transaction, $originalValues);
            }
        });

        return back()->with('success', 'Трансакциите се означени како платени.');

    } catch (\Exception $e) {
        Log::error('Error marking transactions as paid: ' . $e->getMessage());
        return back()->with('error', 'Грешка при означување на трансакциите како платени.');
    }
}

// Add this helper method to DailyTransactionController
private function getTransactionsForDate($date)
{
    return DailyTransaction::with(['breadType', 'company'])
        ->where(function($query) use ($date) {
            $query->where(function($q) use ($date) {
                $q->where('is_paid', true)
                  ->whereDate('paid_date', $date);
            })->orWhere(function($q) use ($date) {
                $q->whereDate('transaction_date', $date);
            });
        })->get();
}


// New method to record payment history
/**
 * Record history of transaction changes
 */
private function recordHistory($transaction, $oldValues, $newValues, $action = 'update')
{
    // Skip if not within tracking hours (12:00 PM to 5:00 AM)
    $currentHour = now()->hour;
    if (!(($currentHour >= 12) || ($currentHour < 5))) {
        return null;
    }
    
    // Skip recording for non-significant changes
    if ($action === 'update') {
        $hasSignificantChanges = false;
        
        $fieldsToCheck = ['delivered', 'returned', 'gratis'];
        foreach ($fieldsToCheck as $field) {
            // Check if the field exists in both old and new values
            if (isset($newValues[$field]) && isset($oldValues[$field])) {
                // Only record if there's an actual change (different values)
                if ($newValues[$field] != $oldValues[$field]) {
                    // Further filter out zero-to-zero changes
                    if (!($newValues[$field] == 0 && $oldValues[$field] == 0)) {
                        $hasSignificantChanges = true;
                        break;
                    }
                }
            } elseif (isset($newValues[$field]) && !isset($oldValues[$field]) && $newValues[$field] != 0) {
                // If new field added with non-zero value
                $hasSignificantChanges = true;
                break;
            }
        }
        
        if (!$hasSignificantChanges) {
            return null;
        }
    }

    // Create filtered new_values and old_values arrays that only contain the fields that actually changed
    $filteredNewValues = [];
    $filteredOldValues = [];
    
    $fieldsToCheck = ['delivered', 'returned', 'gratis'];
    foreach ($fieldsToCheck as $field) {
        if (isset($newValues[$field]) && isset($oldValues[$field]) && $newValues[$field] != $oldValues[$field]) {
            $filteredNewValues[$field] = $newValues[$field];
            $filteredOldValues[$field] = $oldValues[$field];
        }
    }

    // Use create with only the needed fields to reduce query size
    return \App\Models\TransactionHistory::create([
        'transaction_id' => $transaction->id,
        'user_id' => auth()->id(),
        'action' => $action,
        'old_values' => $filteredOldValues,
        'new_values' => $filteredNewValues,
        'ip_address' => request()->ip()
    ]);
}

/**
 * Record payment history
 */
private function recordPaymentHistory($transaction, $oldValues, $newValues)
{
    return $this->recordHistory($transaction, $oldValues, $newValues, 'payment_update');
}

public function getBreadTypePrice($breadTypeId, $companyId)
{
    $cacheKey = "bread_price_{$breadTypeId}_{$companyId}";
    \Cache::forget($cacheKey);


    $breadType = BreadType::find($breadTypeId);
    $company = Company::find($companyId);
    $date = request('date', now()->toDateString());
    
    if (!$breadType || !$company) {
        return response()->json(['error' => 'Invalid bread type or company'], 404);
    }
    
    // First check if there's a specific price in the pivot table
    $specificPrice = DB::table('bread_type_company')
        ->where('bread_type_id', $breadTypeId)
        ->where('company_id', $companyId)
        ->where('valid_from', '<=', $date)
        ->orderBy('valid_from', 'desc')
        ->first();
        
    if ($specificPrice && isset($specificPrice->price)) {
        \Log::debug('API returning specific price from pivot table', ['price' => $specificPrice->price]);
        return response()->json(['price' => $specificPrice->price]);
    }
    
    // If no specific price exists, calculate based on price group
    $priceGroup = $company->price_group;
    
    // Determine price based on company's price group
    if ($priceGroup === 0 || $priceGroup === null) {
        $price = $breadType->price;
    } else {
        $priceGroupField = "price_group_{$priceGroup}";
        if (isset($breadType->$priceGroupField) && $breadType->$priceGroupField > 0) {
            $price = $breadType->$priceGroupField;
        } else {
            $price = $breadType->price;
        }
    }
    
    \Log::debug('API returning calculated price', ['price' => $price]);
    return response()->json(['price' => $price]);
}




public function updateDailyTransaction(Request $request)
{
    try {
        // Validate input
        $validatedData = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'transaction_date' => 'required|date',
            'transactions' => 'required|array',
            'transactions.*.bread_type_id' => 'required|exists:bread_types,id',
            'transactions.*.delivered' => 'required|integer|min:0',
        ]);

  

        DB::beginTransaction();

        $companyId = $validatedData['company_id'];
        $transactionDate = $validatedData['transaction_date'];
        $updatedTransactions = [];

        foreach ($validatedData['transactions'] as $transactionData) {
            // Skip if delivered quantity is 0
            if (empty($transactionData['delivered']) || $transactionData['delivered'] <= 0) {
                continue;
            }

            // Find existing transaction
            $existingTransaction = DailyTransaction::where([
                'company_id' => $companyId,
                'bread_type_id' => $transactionData['bread_type_id'],
                'transaction_date' => $transactionDate
            ])->first();

            if ($existingTransaction) {
                // Store original values for history
                $oldValues = $existingTransaction->only(['delivered', 'returned', 'gratis']);
                
                // Calculate new values
                $newDelivered = $existingTransaction->delivered + $transactionData['delivered'];
                $newReturned = $existingTransaction->returned + ($transactionData['returned'] ?? 0);
                $newGratis = $existingTransaction->gratis + ($transactionData['gratis'] ?? 0);
                
                // Update the values
                $existingTransaction->delivered = $newDelivered;
                $existingTransaction->returned = $newReturned;
                $existingTransaction->gratis = $newGratis;
                $existingTransaction->save();
                
                // Prepare new values for history
                $newValues = [
                    'delivered' => $newDelivered,
                    'returned' => $newReturned,
                    'gratis' => $newGratis
                ];
                
                // Record history
                $this->recordHistory($existingTransaction, $oldValues, $newValues);
                
                $updatedTransactions[] = $existingTransaction->id;
            } else {
                // Create new transaction if it doesn't exist
                $newTransaction = DailyTransaction::create([
                    'company_id' => $companyId,
                    'bread_type_id' => $transactionData['bread_type_id'],
                    'transaction_date' => $transactionDate,
                    'delivered' => $transactionData['delivered'],
                    'returned' => $transactionData['returned'] ?? 0,
                    'gratis' => $transactionData['gratis'] ?? 0,
                    'is_paid' => false
                ]);
                
                $updatedTransactions[] = $newTransaction->id;
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Трансакциите се успешно ажурирани.',
            'updated_transactions' => $updatedTransactions
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Error updating daily transactions: ' . $e->getMessage(), [
            'exception' => $e,
            'request' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Грешка при ажурирање на трансакциите.',
            'error' => $e->getMessage()
        ], 500);
    }
}

// public function updateDailyTransaction(Request $request)
// {
//     try {
//         // Validate input
//         $validatedData = $request->validate([
//             'company_id' => 'required|exists:companies,id',
//             'transaction_date' => 'required|date',
//             'transactions' => 'required|array',
//             'transactions.*.bread_type_id' => 'required|exists:bread_types,id',
//             'transactions.*.delivered' => 'required|integer|min:0',
//         ]);

//         \Log::info('Updating daily transactions', [
//             'company_id' => $validatedData['company_id'],
//             'date' => $validatedData['transaction_date'],
//             'transactions_count' => count($validatedData['transactions'])
//         ]);

//         DB::beginTransaction();

//         $companyId = $validatedData['company_id'];
//         $transactionDate = $validatedData['transaction_date'];
//         $updatedTransactions = [];

//         foreach ($validatedData['transactions'] as $transactionData) {
//             // Skip if delivered quantity is 0
//             if (empty($transactionData['delivered']) || $transactionData['delivered'] <= 0) {
//                 continue;
//             }
        
//             // Find existing transaction
//             $existingTransaction = DailyTransaction::where([
//                 'company_id' => $companyId,
//                 'bread_type_id' => $transactionData['bread_type_id'],
//                 'transaction_date' => $transactionDate
//             ])->first();

//             if ($existingTransaction) {
//                 // Store original values for history
//                 $oldValues = $existingTransaction->only(['delivered', 'returned', 'gratis']);
                
//                 // Calculate new values
//                 $newDelivered = $existingTransaction->delivered + $transactionData['delivered'];
//                 $newReturned = $existingTransaction->returned + ($transactionData['returned'] ?? 0);
//                 $newGratis = $existingTransaction->gratis + ($transactionData['gratis'] ?? 0);
                
//                 // Update the values
//                 $existingTransaction->delivered = $newDelivered;
//                 $existingTransaction->returned = $newReturned;
//                 $existingTransaction->gratis = $newGratis;
//                 $existingTransaction->save();
                
//                 // Prepare new values for history
//                 $newValues = [
//                     'delivered' => $newDelivered,
//                     'returned' => $newReturned,
//                     'gratis' => $newGratis
//                 ];
                
//                 // Record history
//                 $this->recordHistory($existingTransaction, $oldValues, $newValues);
                
//                 $updatedTransactions[] = $existingTransaction->id;
//             }
        
//             // if ($existingTransaction) {
//             //     // Store original values for history
//             //     $oldValues = $existingTransaction->only(['delivered', 'returned', 'gratis']);
                
//             //     // Calculate new values
//             //     $newDelivered = $existingTransaction->delivered + $transactionData['delivered'];
//             //     $newReturned = $existingTransaction->returned + ($transactionData['returned'] ?? 0);
//             //     $newGratis = $existingTransaction->gratis + ($transactionData['gratis'] ?? 0);
                
//             //     // Get the proper price
//             //     $breadType = BreadType::find($transactionData['bread_type_id']);
//             //     $company = Company::find($companyId);
                
//             //     if ($breadType && $company) {
//             //         $price = DailyTransaction::calculatePriceForBreadType(
//             //             $breadType, 
//             //             $company, 
//             //             $transactionDate
//             //         );
                    
//             //         // Update with price
//             //         $existingTransaction->price = $price;
//             //     }
                
//             //     // Update the values
//             //     $existingTransaction->delivered = $newDelivered;
//             //     $existingTransaction->returned = $newReturned;
//             //     $existingTransaction->gratis = $newGratis;
//             //     $existingTransaction->save();

//         // foreach ($validatedData['transactions'] as $transactionData) {
//         //     // Skip if delivered quantity is 0
//         //     if (empty($transactionData['delivered']) || $transactionData['delivered'] <= 0) {
//         //         continue;
//         //     }

//         //     // Find existing transaction
//         //     $existingTransaction = DailyTransaction::where([
//         //         'company_id' => $companyId,
//         //         'bread_type_id' => $transactionData['bread_type_id'],
//         //         'transaction_date' => $transactionDate
//         //     ])->first();

//         //     if ($existingTransaction) {
//         //         // Store original values for history
//         //         $oldValues = $existingTransaction->only(['delivered', 'returned', 'gratis']);
                
//         //         // Calculate new values
//         //         $newDelivered = $existingTransaction->delivered + $transactionData['delivered'];
//         //         $newReturned = $existingTransaction->returned + ($transactionData['returned'] ?? 0);
//         //         $newGratis = $existingTransaction->gratis + ($transactionData['gratis'] ?? 0);
                
//         //         // Update the values
//         //         $existingTransaction->delivered = $newDelivered;
//         //         $existingTransaction->returned = $newReturned;
//         //         $existingTransaction->gratis = $newGratis;
//         //         $existingTransaction->save();
                
//                 // Prepare new values for history
//                 $newValues = [
//                     'delivered' => $newDelivered,
//                     'returned' => $newReturned,
//                     'gratis' => $newGratis
//                 ];
                
//                 // Record history
//                 $this->recordHistory($existingTransaction, $oldValues, $newValues);
                
//                 $updatedTransactions[] = $existingTransaction->id;
//             } else {
//                 // Create new transaction if it doesn't exist
//                 $newTransaction = DailyTransaction::create([
//                     'company_id' => $companyId,
//                     'bread_type_id' => $transactionData['bread_type_id'],
//                     'transaction_date' => $transactionDate,
//                     'delivered' => $transactionData['delivered'],
//                     'returned' => $transactionData['returned'] ?? 0,
//                     'gratis' => $transactionData['gratis'] ?? 0,
//                     'is_paid' => false
//                 ]);
                
//                 $updatedTransactions[] = $newTransaction->id;
//             }
//         }

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'Трансакциите се успешно ажурирани.',
//             'updated_transactions' => $updatedTransactions
//         ]);
//     } catch (\Exception $e) {
//         DB::rollBack();
        
//         \Log::error('Error updating daily transactions: ' . $e->getMessage(), [
//             'exception' => $e,
//             'request' => $request->all()
//         ]);
        
//         return response()->json([
//             'success' => false,
//             'message' => 'Грешка при ажурирање на трансакциите.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

}