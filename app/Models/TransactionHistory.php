<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TransactionHistory extends Model
{
    protected $table = 'transaction_history';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    public function transaction()
    {
        return $this->belongsTo(DailyTransaction::class, 'transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLateNightChange()
    {
        $hour = Carbon::parse($this->created_at)->hour;
        return ($hour >= 11 || $hour < 4);
    }

    public function getChangeType()
    {
        if (!$this->transaction) {
            return null;
        }

        $transactionDate = Carbon::parse($this->transaction->transaction_date);
        $changeDate = Carbon::parse($this->created_at);

        if ($transactionDate->lt($changeDate->startOfDay())) {
            return 'past';
        } elseif ($transactionDate->gt($changeDate->startOfDay())) {
            return 'future';
        }
        return 'current';
    }
}