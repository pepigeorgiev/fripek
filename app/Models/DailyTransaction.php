<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
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
        'user_id',
        // 'price',
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

        // Add a creating event handler to set the correct price
        static::creating(function ($transaction) {
            // Only set price if it's not already set
            // if (!isset($transaction->price) || $transaction->price === null) {
            //     $company = Company::find($transaction->company_id);
            //     $breadType = BreadType::find($transaction->bread_type_id);
                
            //     if ($company && $breadType) {
            //         // Use price groups to calculate the correct price
            //         $transaction->price = self::calculatePriceForBreadType($breadType, $company, $transaction->transaction_date);
                    
            //         // Log the price calculation
            //         Log::info('Setting price for new transaction', [
            //             'bread_type' => $breadType->name,
            //             'company' => $company->name,
            //             'company_price_group' => $company->price_group,
            //             'calculated_price' => $transaction->price
            //         ]);
            //     }
            // }
        });

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
        return self::calculatePriceForBreadType(
            $this->breadType, 
            $this->company, 
            $this->transaction_date
        );
    }

    public static function calculatePriceForBreadType($breadType, $company, $date)
    {
        // First check if there's a specific price in the pivot table
        $specificPrice = DB::table('bread_type_company')
            ->where('bread_type_id', $breadType->id)
            ->where('company_id', $company->id)
            ->where('valid_from', '<=', $date)
            ->orderBy('valid_from', 'desc')
            ->first();
            
        if ($specificPrice && isset($specificPrice->price)) {
            \Log::debug('Using specific price from pivot table', ['price' => $specificPrice->price]);
            return $specificPrice->price;
        }
        
        // If no specific price exists, calculate based on price group
        $priceGroup = $company->price_group;
        \Log::debug('Calculating price for bread type', [
            'bread_type' => $breadType->name,
            'company' => $company->name,
            'company_price_group' => $priceGroup,
            'date' => $date
        ]);
        
        // Determine price based on company's price group
        if ($priceGroup === 0 || $priceGroup === null) {
            return $breadType->price;
        } else {
            $priceGroupField = "price_group_{$priceGroup}";
            if (isset($breadType->$priceGroupField) && $breadType->$priceGroupField > 0) {
                return $breadType->$priceGroupField;
            } else {
                return $breadType->price;
            }
        }
    }
    
    
    // public static function calculatePriceForBreadType(BreadType $breadType, Company $company, $date)
    // {
    //     // Log the call for debugging
    //     Log::debug('Calculating price for bread type', [
    //         'bread_type' => $breadType->name,
    //         'company' => $company->name,
    //         'company_price_group' => $company->price_group,
    //         'date' => $date instanceof \DateTime ? $date->format('Y-m-d') : $date
    //     ]);
        
    //     // Format date if it's a DateTime object
    //     if ($date instanceof \DateTime) {
    //         $date = $date->format('Y-m-d');
    //     }
        
    //     // Step 1: Check for specific price in the pivot table
    //     $specificPrice = DB::table('bread_type_company')
    //         ->where('bread_type_id', $breadType->id)
    //         ->where('company_id', $company->id)
    //         ->where('valid_from', '<=', $date)
    //         ->orderBy('valid_from', 'desc')
    //         ->value('price');
            
    //     if ($specificPrice !== null) {
    //         Log::debug('Using specific price from pivot table', ['price' => $specificPrice]);
    //         return $specificPrice;
    //     }
        
    //     // Step 2: Use company's price group if set
    //     $priceGroup = $company->price_group;
    //     if ($priceGroup > 0) {
    //         $priceGroupField = "price_group_{$priceGroup}";
            
    //         if (isset($breadType->$priceGroupField) && $breadType->$priceGroupField !== null) {
    //             Log::debug("Using price group {$priceGroup}", ['price' => $breadType->$priceGroupField]);
    //             return $breadType->$priceGroupField;
    //         }
    //     }
        
    //     // Step 3: Fall back to default price
    //     Log::debug('Using default price', ['price' => $breadType->price]);
    //     return $breadType->price;
    // }

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Modify the getTotalPriceAttribute to use the new price calculation
    public function getTotalPriceAttribute()
    {
        if (!$this->breadType || !$this->is_paid) {
            return 0;
        }

        $price = self::calculatePriceForBreadType(
            $this->breadType, 
            $this->company, 
            $this->transaction_date
        );

        Log::info('=== PRICE CALCULATION ===', [
            'transaction_id' => $this->id,
            'bread_type' => optional($this->breadType)->name,
            'company' => optional($this->company)->name,
            'company_price_group' => optional($this->company)->price_group,
            'price' => $price,
            'net_amount' => $this->net_amount,
            'is_paid' => $this->is_paid
        ]);

        return $this->net_amount * $price;
    }
    
    // Helper property to calculate net amount
    public function getNetAmountAttribute()
    {
        return ($this->delivered ?? 0) - ($this->returned ?? 0) - ($this->gratis ?? 0);
    }
}