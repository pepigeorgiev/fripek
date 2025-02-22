<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\BreadSale;
// use App\Models\DailyTransaction;
// use App\Models\User;
// use App\Models\Company;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Auth;

// class OldBreadSalesController extends Controller
// {

//     public function store(Request $request)
// {
//     Log::info('Received request to save old bread sales', [
//         'request_data' => $request->all()
//     ]);

//     $request->validate([
//         'transaction_date' => 'required|date',
//         'old_bread_sold.*.bread_type_id' => 'required|exists:bread_types,id',
//         'old_bread_sold.*.sold' => 'required|integer|min:0',
//     ]);

//     $transactionDate = $request->input('transaction_date');
//     $oldBreadSold = $request->input('old_bread_sold', []);
//     $user = Auth::user();

//     try {
//         DB::beginTransaction();

//         $company = $user->companies()->first() ?? Company::first();
        
//         if (!$company) {
//             throw new \Exception('No company found for user');
//         }

//         foreach ($oldBreadSold as $breadTypeId => $data) {
//             $amount = (int)$data['sold'];
            
//             // Get existing transaction to add to its value
//             $existingTransaction = DailyTransaction::where([
//                 'bread_type_id' => $breadTypeId,
//                 'transaction_date' => $transactionDate,
//                 'user_id' => $user->id,
//                 'company_id' => $company->id
//             ])->first();

//             $newAmount = $amount;
//             if ($existingTransaction) {
//                 $newAmount += $existingTransaction->old_bread_sold;
//             }

//             // Update or create with accumulated value
//             DailyTransaction::updateOrCreate(
//                 [
//                     'bread_type_id' => $breadTypeId,
//                     'transaction_date' => $transactionDate,
//                     'user_id' => $user->id,
//                     'company_id' => $company->id
//                 ],
//                 [
//                     'old_bread_sold' => $newAmount,
//                     'delivered' => 0,
//                     'returned' => 0,
//                     'gratis' => 0,
//                     'is_paid' => true
//                 ]
//             );

//             Log::info('Updated/Created daily transaction', [
//                 'bread_type_id' => $breadTypeId,
//                 'original_amount' => $amount,
//                 'accumulated_amount' => $newAmount,
//                 'user_id' => $user->id,
//                 'company_id' => $company->id
//             ]);
//         }

//         DB::commit();

//         return redirect()->route('daily-transactions.create')
//                         ->with('success', 'Успешно ажурирање на стар леб');

//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Error storing old bread sales: ' . $e->getMessage());
        
//         return redirect()->back()
//                         ->with('error', 'Грешка при зачувување на продажбата на стар леб: ' . $e->getMessage());
//     }
// }


//     public function getOldBreadSalesData($date)
//     {
//         $user = Auth::user();
        
//         return DailyTransaction::where('transaction_date', $date)
//                        ->where('user_id', $user->id)
//                        ->whereNotNull('old_bread_sold')
//                        ->select('bread_type_id', 'old_bread_sold as sold')
//                        ->get()
//                        ->keyBy('bread_type_id')
//                        ->toArray();
//     }
// }