<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'bread_type', 'delivered', 'returned', 'price'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}