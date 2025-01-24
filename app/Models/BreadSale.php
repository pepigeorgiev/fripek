<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadSale extends Model
{
    // protected $fillable = ['transaction_date', 'bread_type_id', 'sold_amount', 'returned_amount','total_amount']; // Add returned_amount

    public function breadType()
    {
        return $this->belongsTo(BreadType::class);
    }
    public function company()
{
    return $this->belongsTo(Company::class);
}
protected $fillable = [
    'bread_type_id',
    "company_id",
    'transaction_date',
    'returned_amount',
    'sold_amount',
    'total_amount',
    'old_bread_sold',
    'old_bread_total',
    'returned_amount_1'  // Make sure this is included

];
}

