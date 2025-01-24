<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'code',
        'report_end_date'
    ];

    protected $casts = [
        'report_end_date' => 'date'
    ];

    protected $casts1 = [
        'type' => 'string'
    ];
    



    public function monthlyTransactions()
    {
        return $this->hasMany(MonthlySummary::class);
    }

    public function hasAnyTransactions()
    {
        return $this->dailyTransactions()->exists() 
            || $this->monthlyTransactions()->exists();
    }

    

    
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user');
    }

    public function dailyTransactions()
    {
        return $this->hasMany(DailyTransaction::class);
    }
    }
