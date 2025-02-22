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

// In DailyTransactionController
public function storeOldBreadSales(Request $request)
{
    Log::info('Processing old bread sales request', [
        'request_data' => $request->all()
    ]);

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

            // Log the accumulation
            Log::info('Accumulating old bread sales', [
                'bread_type_id' => $data['bread_type_id'],
                'current_amount' => $currentAmount,
                'adding_amount' => $data['sold'],
                'new_total' => $newAmount
            ]);

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

    $price = $transaction->breadType->getPriceForCompany($company->id, $date)['price'];
    $oldBreadPrice = $transaction->breadType->old_price ?? $price;
    
    return [
        'netBreads' => $netBreads,
        'price' => $price,
        'total' => $netBreads * $price,
        'old_bread_sold' => $oldBreadSold,
        'old_bread_total' => $oldBreadSold * $oldBreadPrice
    ];
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

}