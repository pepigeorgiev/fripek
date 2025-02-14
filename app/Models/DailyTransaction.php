<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SuspiciousChangeNotification;
use Carbon\Carbon;
use App\Traits\TracksHistory;

class DailyTransaction extends Model
{
    use TracksHistory;

    protected $fillable = [
        'company_id',
        'bread_type_id',
        'transaction_date',
        'delivered',
        'returned',
        'gratis',
        'is_paid',
        'old_bread_sold'
    ];

    protected $dates = ['transaction_date'];

    protected $casts = [
        'transaction_date' => 'date',
    ];


    
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($transaction) {
            $changes = $transaction->getDirty();
            $oldValues = array_intersect_key($transaction->getOriginal(), $changes);
            
            // Only track if there are actual changes
            if (!empty($changes)) {
                $currentHour = Carbon::now()->hour;
                $isLateNightEdit = ($currentHour >= 11 || $currentHour < 4);
                $isNotCurrentDate = !$transaction->transaction_date->isToday();
                
                // Log the attempt
                Log::info('Transaction update detected', [
                    'transaction_id' => $transaction->id,
                    'user' => auth()->user()->name,
                    'current_hour' => $currentHour,
                    'is_late_night' => $isLateNightEdit,
                    'transaction_date' => $transaction->transaction_date,
                    'is_not_current_date' => $isNotCurrentDate,
                    'changes' => $changes,
                    'old_values' => $oldValues
                ]);

                // Create history record if it's late night or not current date
                if ($isLateNightEdit || $isNotCurrentDate) {
                    try {
                        TransactionHistory::create([
                            'transaction_id' => $transaction->id,
                            'user_id' => auth()->id(),
                            'action' => 'update',
                            'old_values' => $oldValues,
                            'new_values' => $changes,
                            'ip_address' => request()->ip()
                        ]);

                        Log::info('History record created successfully', [
                            'transaction_id' => $transaction->id,
                            'user' => auth()->user()->name
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create history record', [
                            'error' => $e->getMessage(),
                            'transaction_id' => $transaction->id
                        ]);
                    }
                }

                // Check for suspicious changes (more than 20 pieces)
                if (isset($changes['delivered']) || isset($changes['returned'])) {
                    $oldDelivered = $oldValues['delivered'] ?? 0;
                    $oldReturned = $oldValues['returned'] ?? 0;
                    $newDelivered = $changes['delivered'] ?? $oldDelivered;
                    $newReturned = $changes['returned'] ?? $oldReturned;
                    
                    if (abs(($newDelivered - $newReturned) - ($oldDelivered - $oldReturned)) > 20) {
                        Log::channel('suspicious')->warning(
                            "Large quantity change detected for transaction ID: {$transaction->id} " .
                            "by user: " . auth()->user()->name . " at " . now()->format('H:i:s')
                        );
                        
                        Notification::route('mail', config('app.admin_email'))
                            ->notify(new SuspiciousChangeNotification($transaction));
                    }
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getPrice()
    {
        return $this->breadType->getCurrentPrice($this->company_id, $this->transaction_date);
    }

    public function breadType()
    {
        return $this->belongsTo(BreadType::class, 'bread_type_id');
    }

    // Add this scope for unpaid transactions
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    // Add this scope for paid transactions
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    // Modify the getTotalPriceAttribute to consider payment status
    public function getTotalPriceAttribute()
    {
        if (!$this->breadType || !$this->is_paid) {
            return 0;
        }

        $prices = $this->breadType->getPriceForCompany(
            $this->company_id, 
            $this->transaction_date
        );

        Log::info('=== PRICE CALCULATION ===', [
            'transaction_id' => $this->id,
            'bread_type' => optional($this->breadType)->name,
            'company' => optional($this->company)->name,
            'price' => $prices['price'] ?? 0,
            'net_amount' => $this->net_amount,
            'is_paid' => $this->is_paid
        ]);

        return $this->net_amount * ($prices['price'] ?? 0);
    }
}