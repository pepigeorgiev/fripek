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
    
    public function create()
    {
        $user = Auth::user();
        $companies = $user->isAdmin() ? Company::all() : $user->companies;
        $breadTypes = BreadType::where('is_active', true)->get();
        
        // Get date and company_id from request
        $date = request('date', now()->toDateString());
        $selectedCompanyId = request('company_id');
        
    
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

                    // Create new transaction without affecting old_bread_sold
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
                        ]
                    );

                    // Record history if there was an existing transaction
                    if (isset($existingTransactions[$transaction['bread_type_id']])) {
                        $oldTransaction = $existingTransactions[$transaction['bread_type_id']];
                        $this->recordHistory($newTransaction, [
                            'delivered' => $oldTransaction->delivered,
                            'returned' => $oldTransaction->returned,
                            'gratis' => $oldTransaction->gratis
                        ], [
                            'delivered' => $transaction['delivered'],
                            'returned' => $transaction['returned'],
                            'gratis' => $transaction['gratis']
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
    $validatedData = $request->validate([
        'transaction_date' => 'required|date',
        'old_bread_sold' => 'required|array',
        'old_bread_sold.*.bread_type_id' => 'required|exists:bread_types,id',
        'old_bread_sold.*.sold' => 'required|integer|min:0'
    ]);

    try {
        DB::transaction(function () use ($validatedData) {
            $user = Auth::user();
            $company = $user->companies()->first();

            foreach ($validatedData['old_bread_sold'] as $breadTypeId => $data) {
                // Update only old_bread_sold without affecting other fields
                DailyTransaction::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'bread_type_id' => $data['bread_type_id'],
                        'transaction_date' => $validatedData['transaction_date']
                    ],
                    [
                        'old_bread_sold' => $data['sold']
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Успешно ажурирање на стар леб.'
        ]);
    } catch (\Exception $e) {
        Log::error('Error storing old bread sales: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Грешка при зачувување.'
        ], 500);
    }
}










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
// public function markAsPaid(Request $request)
// {
//     try {
//         DB::transaction(function () use ($request) {
//             $date = $request->input('date');
//             $companyId = $request->input('company_id');
            
//             Log::info('Starting markAsPaid process', [
//                 'date' => $date,
//                 'company_id' => $companyId
//             ]);

//             // Get all unpaid transactions for this company and date
//             $unpaidTransactions = DailyTransaction::where([
//                 'company_id' => $companyId,
//                 'transaction_date' => $date,
//                 'is_paid' => false
//             ])->get();

//             foreach ($unpaidTransactions as $unpaidTransaction) {
//                 // Check if there's an existing transaction for today
//                 $existingTransaction = DailyTransaction::where([
//                     'company_id' => $companyId,
//                     'bread_type_id' => $unpaidTransaction->bread_type_id,
//                     'transaction_date' => now()->toDateString()
//                 ])->first();

//                 if ($existingTransaction) {
//                     Log::info('Found existing transaction', [
//                         'transaction_id' => $existingTransaction->id,
//                         'bread_type_id' => $unpaidTransaction->bread_type_id
//                     ]);

//                     // Keep the existing transaction's values but mark it as paid
//                     $oldValues = [
//                         'delivered' => $existingTransaction->delivered,
//                         'returned' => $existingTransaction->returned,
//                         'gratis' => $existingTransaction->gratis,
//                         'is_paid' => $existingTransaction->is_paid
//                     ];

//                     $existingTransaction->update([
//                         'is_paid' => true,
//                         'paid_date' => now()->toDateString()
//                     ]);

//                     // Record history for the existing transaction
//                     $this->recordHistory($existingTransaction, $oldValues, [
//                         'delivered' => $existingTransaction->delivered,
//                         'returned' => $existingTransaction->returned,
//                         'gratis' => $existingTransaction->gratis,
//                         'is_paid' => true
//                     ]);
//                 }

//                 // Also mark the original unpaid transaction as paid
//                 $oldValues = [
//                     'delivered' => $unpaidTransaction->delivered,
//                     'returned' => $unpaidTransaction->returned,
//                     'gratis' => $unpaidTransaction->gratis,
//                     'is_paid' => false
//                 ];

//                 $unpaidTransaction->update([
//                     'is_paid' => true,
//                     'paid_date' => now()->toDateString()
//                 ]);

//                 // Record history for the unpaid transaction
//                 $this->recordHistory($unpaidTransaction, $oldValues, [
//                     'delivered' => $unpaidTransaction->delivered,
//                     'returned' => $unpaidTransaction->returned,
//                     'gratis' => $unpaidTransaction->gratis,
//                     'is_paid' => true
//                 ]);

//                 Log::info('Transaction marked as paid', [
//                     'transaction_id' => $unpaidTransaction->id,
//                     'bread_type_id' => $unpaidTransaction->bread_type_id
//                 ]);
//             }
//         });

//         return back()->with('success', 'Трансакциите се означени како платени.');

//     } catch (\Exception $e) {
//         Log::error('Error marking transactions as paid: ' . $e->getMessage(), [
//             'trace' => $e->getTraceAsString()
//         ]);
//         return back()->with('error', 'Грешка при означување на трансакциите како платени.');
//     }
// }

// New method to record payment history
private function recordPaymentHistory($transaction, $oldValues, $newValues)
{
    return $this->recordHistory($transaction, $oldValues, $newValues, 'payment_update');
}




public function updateDailyTransaction(Request $request)
{
    $validatedData = $request->validate([
        'company_id' => 'required|exists:companies,id',
        'transaction_date' => 'required|date',
        'transactions' => 'required|array',
        'transactions.*.bread_type_id' => 'required|exists:bread_types,id',
        'transactions.*.delivered' => 'required|integer|min:0',
    ]);

    try {
        DB::transaction(function () use ($validatedData) {
            $companyId = $validatedData['company_id'];
            $transactionDate = $validatedData['transaction_date'];

            foreach ($validatedData['transactions'] as $transactionData) {
                // Find existing transaction for this company, date, and bread type
                $existingTransaction = DailyTransaction::where([
                    'company_id' => $companyId,
                    'bread_type_id' => $transactionData['bread_type_id'],
                    'transaction_date' => $transactionDate
                ])->first();

                if ($existingTransaction) {
                    // Update existing transaction by adding new quantities
                    $oldValues = $existingTransaction->only(['delivered', 'returned', 'gratis']);
                    
                    $existingTransaction->update([
                        'delivered' => $existingTransaction->delivered + $transactionData['delivered'],
                        'returned' => $existingTransaction->returned + ($transactionData['returned'] ?? 0),
                        'gratis' => $existingTransaction->gratis + ($transactionData['gratis'] ?? 0)
                    ]);

                    // Optional: Record history of the update
                    $this->recordHistory($existingTransaction, $oldValues, [
                        'delivered' => $existingTransaction->delivered,
                        'returned' => $existingTransaction->returned,
                        'gratis' => $existingTransaction->gratis
                    ]);
                } else {
                    // Create new transaction if it doesn't exist
                    DailyTransaction::create([
                        'company_id' => $companyId,
                        'bread_type_id' => $transactionData['bread_type_id'],
                        'transaction_date' => $transactionDate,
                        'delivered' => $transactionData['delivered'],
                        'returned' => $transactionData['returned'] ?? 0,
                        'gratis' => $transactionData['gratis'] ?? 0,
                        'is_paid' => false
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Трансакциите се успешно ажурирани.'
        ]);
    } catch (\Exception $e) {
        Log::error('Error updating daily transactions: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Грешка при ажурирање на трансакциите.'
        ], 500);
    }
}

//     public function updateDailyTransaction(Request $request, Company $company)
//     {
//         $data = $request->validate([
//             'date' => 'required|date',
//             'transactions' => 'required|array',
//             'transactions.*.bread_type_id' => [
//                 'required',
//                 'exists:bread_types,id',
//                 function ($attribute, $value, $fail) {
//                     $breadType = BreadType::find($value);
//                     if (!$breadType || !$breadType->is_active) {
//                         $fail('Selected bread type is not active.');
//                     }
//                 },
//             ],
//             'transactions.*.delivered' => 'required|integer|min:0',
//             'transactions.*.returned' => 'required|integer|min:0',
//             'transactions.*.gratis' => 'required|integer|min:0',
//         ]);

//         foreach ($data['transactions'] as $transaction) {
//             // Double-check bread type is active before creating/updating
//             $breadType = BreadType::find($transaction['bread_type_id']);
//             if ($breadType && $breadType->is_active) {
//                 // Get existing transaction first
//                 $existingTransaction = DailyTransaction::where([
//                     'company_id' => $company->id,
//                     'bread_type_id' => $transaction['bread_type_id'],
//                     'transaction_date' => $data['date'],
//                 ])->first();

//                 // Prepare old values if transaction exists
//                 $oldValues = $existingTransaction ? [
//                     'delivered' => $existingTransaction->delivered,
//                     'returned' => $existingTransaction->returned,
//                     'gratis' => $existingTransaction->gratis,
//                 ] : null;

//                 // Create or update the transaction
//                 $dailyTransaction = DailyTransaction::updateOrCreate(
//                     [
//                         'company_id' => $company->id,
//                         'bread_type_id' => $transaction['bread_type_id'],
//                         'transaction_date' => $data['date'],
//                     ],
//                     [
//                         'delivered' => $transaction['delivered'],
//                         'returned' => $transaction['returned'],
//                         'gratis' => $transaction['gratis'],
//                     ]
//                 );

//                 // Record history if there are changes
//                 if ($oldValues !== null) {
//                     $newValues = [
//                         'delivered' => $transaction['delivered'],
//                         'returned' => $transaction['returned'],
//                         'gratis' => $transaction['gratis'],
//                     ];
                    
//                     if ($oldValues != $newValues) {
//                         $this->recordHistory($dailyTransaction, $oldValues, $newValues);
//                     }
//                 }
//             }
//         }

//         // Get updated summary for active bread types only
//         $todaySummary = DailyTransaction::where('company_id', $company->id)
//             ->whereDate('transaction_date', $data['date'])
//             ->whereHas('breadType', function($query) {
//                 $query->where('is_active', true);
//             })
//             ->with(['breadType' => function($query) {
//                 $query->where('is_active', true);
//             }])
//             ->get();

//         $month = Carbon::parse($data['date'])->format('m');
//         $year = Carbon::parse($data['date'])->format('Y');

//         // Get monthly transactions for active bread types only
//         $monthlyTransactions = DailyTransaction::with(['breadType' => function($query) {
//                 $query->where('is_active', true);
//             }])
//             ->whereHas('breadType', function($query) {
//                 $query->where('is_active', true);
//             })
//             ->where('company_id', $company->id)
//             ->whereMonth('transaction_date', $month)
//             ->whereYear('transaction_date', $year)
//             ->get()
//             ->groupBy(function ($transaction) {
//                 return $transaction->transaction_date->format('Y-m-d');
//             });

//         return response()->json([
//             'success' => true,
//             'todaySummary' => $todaySummary,
//             'monthlyTransactions' => $monthlyTransactions,
//         ]);
//     }
    
}