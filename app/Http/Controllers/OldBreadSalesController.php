<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BreadSale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OldBreadSalesController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Received request to save old bread sales', [
            'request_data' => $request->all()
        ]);

        $request->validate([
            'transaction_date' => 'required|date',
            'old_bread_sold.*.bread_type_id' => 'required|exists:bread_types,id',
            'old_bread_sold.*.sold' => 'required|integer|min:0',
        ]);

        $transactionDate = $request->input('transaction_date');
        $oldBreadSold = $request->input('old_bread_sold', []);

        try {
            DB::beginTransaction();

            foreach ($oldBreadSold as $breadTypeId => $data) {
                // Get existing record for this bread type and date
                $existingRecord = BreadSale::where('bread_type_id', $breadTypeId)
                    ->where('transaction_date', $transactionDate)
                    ->whereNotNull('old_bread_sold')
                    ->first();

                if ($existingRecord) {
                    // Add new amount to existing amount
                    $existingRecord->old_bread_sold += $data['sold'];
                    $existingRecord->save();
                } else {
                    // Create new record
                    BreadSale::create([
                        'bread_type_id' => $breadTypeId,
                        'transaction_date' => $transactionDate,
                        'old_bread_sold' => $data['sold'],
                        'user_id' => auth()->id(),
                        'company_id' => null, // Explicitly set to null
                    ]);
                    
                    // BreadSale::create([
                    //     'bread_type_id' => $breadTypeId,
                    //     'transaction_date' => $transactionDate,
                    //     'old_bread_sold' => $data['sold'],
                    //     'user_id' => auth()->id()
                    // ]);
                }
            }

            DB::commit();
            return redirect()->route('daily-transactions.create')
                           ->with('success', 'Продажбата на стар леб е успешно зачувана.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing old bread sales: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'Грешка при зачувување на продажбата на стар леб.');
        }
    }

    public function getOldBreadSalesData($date)
    {
        return BreadSale::where('transaction_date', $date)
                       ->whereNotNull('old_bread_sold')
                       ->select('bread_type_id', 'old_bread_sold as sold')
                       ->get()
                       ->keyBy('bread_type_id')
                       ->toArray();
    }
}