<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadPriceHistory extends Model
{
    protected $table = 'bread_price_history';
    
    protected $fillable = [
        'bread_type_id',
        'price',
        'old_price',
        'valid_from',
        'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'valid_from' => 'date'
    ];

    public function breadType()
    {
        return $this->belongsTo(BreadType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}