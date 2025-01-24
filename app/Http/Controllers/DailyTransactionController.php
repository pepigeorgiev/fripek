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
            
            // Get existing transactions before deletion for history
            $existingTransactions = DailyTransaction::where([
                'company_id' => $validatedData['company_id'],
                'transaction_date' => $validatedData['transaction_date']
            ])->get()->keyBy('bread_type_id');
            
            // First, delete existing transactions for this company and date
            DailyTransaction::where([
                'company_id' => $validatedData['company_id'],
                'transaction_date' => $validatedData['transaction_date']
            ])->delete();

            // Create new transactions
            foreach ($validatedData['transactions'] as $transaction) {
                $breadType = BreadType::find($transaction['bread_type_id']);
                if ($breadType && $breadType->is_active) {
                    $transactionIsPaid = $shouldTrackPayment ? $isPaid : true;
                    $paidDate = $transactionIsPaid ? now()->toDateString() : null;
                    
                    $newTransaction = DailyTransaction::create([
                        'company_id' => $validatedData['company_id'],
                        'transaction_date' => $validatedData['transaction_date'],
                        'bread_type_id' => $transaction['bread_type_id'],
                        'delivered' => $transaction['delivered'],
                        'returned' => $transaction['returned'],
                        'gratis' => $transaction['gratis'],
                        'is_paid' => $transactionIsPaid,
                        'paid_date' => $paidDate,
                    ]);

                    // Record history if there was an existing transaction
                    if (isset($existingTransactions[$transaction['bread_type_id']])) {
                        $oldTransaction = $existingTransactions[$transaction['bread_type_id']];
                        
                        // Use TransactionHistory::create directly
                        TransactionHistory::create([
                            'transaction_id' => $newTransaction->id,
                            'user_id' => auth()->id(),
                            'action' => 'update',
                            'old_values' => [
                                'delivered' => $oldTransaction->delivered,
                                'returned' => $oldTransaction->returned,
                                'gratis' => $oldTransaction->gratis
                            ],
                            'new_values' => [
                                'delivered' => $transaction['delivered'],
                                'returned' => $transaction['returned'],
                                'gratis' => $transaction['gratis']
                            ],
                            'ip_address' => request()->ip()
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



public function markAsPaid(Request $request)
{
    try {
        DB::transaction(function () use ($request) {
            $date = $request->input('date');
            $companyId = $request->input('company_id');
            
            // Simply update all unpaid transactions for this company and date
            DailyTransaction::where([
                'company_id' => $companyId,
                'transaction_date' => $date,
                'is_paid' => false
            ])->update([
                'is_paid' => true,
                'paid_date' => now()->toDateString()
            ]);
        });

        return back()->with('success', 'Трансакциите се означени како платени.');

    } catch (\Exception $e) {
        Log::error('Error marking transactions as paid: ' . $e->getMessage());
        return back()->with('error', 'Грешка при означување на трансакциите како платени.');
    }
}

// New method to record payment history
private function recordPaymentHistory($transaction, $oldValues, $newValues)
{
    return $this->recordHistory($transaction, $oldValues, $newValues, 'payment_update');
}





    public function updateDailyTransaction(Request $request, Company $company)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'transactions' => 'required|array',
            'transactions.*.bread_type_id' => [
                'required',
                'exists:bread_types,id',
                function ($attribute, $value, $fail) {
                    $breadType = BreadType::find($value);
                    if (!$breadType || !$breadType->is_active) {
                        $fail('Selected bread type is not active.');
                    }
                },
            ],
            'transactions.*.delivered' => 'required|integer|min:0',
            'transactions.*.returned' => 'required|integer|min:0',
            'transactions.*.gratis' => 'required|integer|min:0',
        ]);

        foreach ($data['transactions'] as $transaction) {
            // Double-check bread type is active before creating/updating
            $breadType = BreadType::find($transaction['bread_type_id']);
            if ($breadType && $breadType->is_active) {
                // Get existing transaction first
                $existingTransaction = DailyTransaction::where([
                    'company_id' => $company->id,
                    'bread_type_id' => $transaction['bread_type_id'],
                    'transaction_date' => $data['date'],
                ])->first();

                // Prepare old values if transaction exists
                $oldValues = $existingTransaction ? [
                    'delivered' => $existingTransaction->delivered,
                    'returned' => $existingTransaction->returned,
                    'gratis' => $existingTransaction->gratis,
                ] : null;

                // Create or update the transaction
                $dailyTransaction = DailyTransaction::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'bread_type_id' => $transaction['bread_type_id'],
                        'transaction_date' => $data['date'],
                    ],
                    [
                        'delivered' => $transaction['delivered'],
                        'returned' => $transaction['returned'],
                        'gratis' => $transaction['gratis'],
                    ]
                );

                // Record history if there are changes
                if ($oldValues !== null) {
                    $newValues = [
                        'delivered' => $transaction['delivered'],
                        'returned' => $transaction['returned'],
                        'gratis' => $transaction['gratis'],
                    ];
                    
                    if ($oldValues != $newValues) {
                        $this->recordHistory($dailyTransaction, $oldValues, $newValues);
                    }
                }
            }
        }

        // Get updated summary for active bread types only
        $todaySummary = DailyTransaction::where('company_id', $company->id)
            ->whereDate('transaction_date', $data['date'])
            ->whereHas('breadType', function($query) {
                $query->where('is_active', true);
            })
            ->with(['breadType' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $month = Carbon::parse($data['date'])->format('m');
        $year = Carbon::parse($data['date'])->format('Y');

        // Get monthly transactions for active bread types only
        $monthlyTransactions = DailyTransaction::with(['breadType' => function($query) {
                $query->where('is_active', true);
            }])
            ->whereHas('breadType', function($query) {
                $query->where('is_active', true);
            })
            ->where('company_id', $company->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'todaySummary' => $todaySummary,
            'monthlyTransactions' => $monthlyTransactions,
        ]);
    }
    
}