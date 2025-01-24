<?php

namespace App\Http\Requests;

use App\Models\DailyTransaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreDailyTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'transaction_date' => 'required|date',
            'transactions' => 'required|array',
            'transactions.*.bread_type_id' => 'required|exists:bread_types,id',
            'transactions.*.delivered' => 'required|integer|min:0',
            'transactions.*.returned' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'Please select a company',
            'transaction_date.required' => 'Please select a date',
            'transactions.*.delivered.min' => 'Delivered amount must be 0 or greater',
            'transactions.*.returned.min' => 'Returned amount must be 0 or greater',
        ];
    }

    public function store(StoreDailyTransactionRequest $request)
{
    $validatedData = $request->validated();

    foreach ($validatedData['transactions'] as $transaction) {
        DailyTransaction::create([
            'company_id' => $validatedData['company_id'],
            'transaction_date' => $validatedData['transaction_date'],
            'bread_type_id' => $transaction['bread_type_id'],
            'delivered' => $transaction['delivered'],
            'returned' => $transaction['returned'],
        ]);
    }

    return redirect()->route('dashboard')->with('success', 'Daily transaction recorded successfully');
}
}

