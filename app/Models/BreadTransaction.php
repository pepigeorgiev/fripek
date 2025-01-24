<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadTransaction extends Model
{
    protected $fillable = [
        'date',
        'bread_type_id',
        'sold_old_bread',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function breadType()
    {
        return $this->belongsTo(BreadType::class);
    }
}
