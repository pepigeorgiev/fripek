<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Define relationship with DailyTransaction
     * Using select to only get necessary fields
     */
    public function transaction()
    {
        return $this->belongsTo(DailyTransaction::class, 'transaction_id')
            ->select(['id', 'transaction_date', 'company_id', 'bread_type_id']);
    }

    /**
     * Define relationship with User
     * Using select to only get necessary fields
     */
    public function user()
    {
        return $this->belongsTo(User::class)
            ->select(['id', 'name']);
    }

    /**
     * Check if change was made during late night hours
     * Cached to avoid repeated calculations
     */
    public function isLateNightChange()
    {
        $hour = Carbon::parse($this->created_at)->hour;
        return ($hour >= 23 || $hour < 5); // Adjusted to 11PM-5AM (assuming you meant 23 not 11)
    }

    /**
     * Get the type of change based on transaction date vs. created_at
     * Optimized to avoid unnecessary processing when transaction is null
     */
    public function getChangeType()
    {
        if (!$this->transaction) {
            return null;
        }

        // Cache the result to avoid recalculation
        $cacheKey = "change_type_{$this->id}";
        
        return Cache::remember($cacheKey, 60, function() {
            $transactionDate = Carbon::parse($this->transaction->transaction_date);
            $changeDate = Carbon::parse($this->created_at);

            if ($transactionDate->lt($changeDate->startOfDay())) {
                return 'past';
            } elseif ($transactionDate->gt($changeDate->startOfDay())) {
                return 'future';
            }
            return 'current';
        });
    }

    /**
     * Scope to get only suspicious changes
     */
    public function scopeSuspicious($query)
    {
        return $query->where(function($q) {
            // Late night changes (11PM to 5AM)
            $q->whereRaw('HOUR(created_at) >= 23 OR HOUR(created_at) < 5')
              // Or changes to past dates
              ->orWhereHas('transaction', function($subq) {
                  $subq->whereRaw('transaction_date < DATE(transaction_history.created_at)');
              });
        });
    }
}