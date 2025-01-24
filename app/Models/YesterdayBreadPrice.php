<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['bread_type', 'returned', 'sold', 'transaction_date', 'price'];

    // If you want to use 'transaction_date' instead of 'date'
    protected $dates = ['transaction_date'];

    public function breadType()
    {
        return $this->belongsTo(BreadType::class, 'bread_type', 'name');
    }
}

