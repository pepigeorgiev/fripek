<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\TransactionHistory;

trait TracksHistory
{
    protected $originalValues = [];
    protected static $hasBooted = false;
    
    protected static function bootTracksHistory()
    {
        // Only boot once per request to avoid multiple initializations
        if (self::$hasBooted) {
            return;
        }
        self::$hasBooted = true;
        
        // Creating event
        static::creating(function ($transaction) {
            // Quickly return if not in tracking hours
            if (!self::shouldTrackChanges()) {
                return;
            }

            // Check for existing transaction with a single, optimized query
            $existingTransaction = \DB::table('daily_transactions')
                ->select(['delivered', 'returned', 'gratis'])
                ->where('company_id', $transaction->company_id)
                ->where('bread_type_id', $transaction->bread_type_id)
                ->where('transaction_date', $transaction->transaction_date)
                ->first();

            if ($existingTransaction) {
                // Store only necessary fields to reduce memory usage
                $transaction->originalValues = [
                    'delivered' => $existingTransaction->delivered,
                    'returned' => $existingTransaction->returned,
                    'gratis' => $existingTransaction->gratis ?? 0
                ];
            }
        });

        // Created event
        static::created(function ($transaction) {
            if (!self::shouldTrackChanges()) {
                return;
            }

            try {
                // Only get necessary attributes to track
                $newValues = [
                    'delivered' => $transaction->delivered,
                    'returned' => $transaction->returned,
                    'gratis' => $transaction->gratis ?? 0
                ];
                
                static::createHistoryRecord(
                    $transaction, 
                    $transaction->originalValues ?? [], 
                    $newValues,
                    isset($transaction->originalValues) ? 'update' : 'create'
                );
            } catch (\Exception $e) {
                // Only log actual errors
                Log::error('Failed to record create history: ' . $e->getMessage(), [
                    'transaction_id' => $transaction->id
                ]);
            }
        });

        // Updating event
        static::updating(function ($transaction) {
            if (!self::shouldTrackChanges()) {
                return;
            }

            // Only fetch original values if we're going to use them
            $originalTransaction = \DB::table('daily_transactions')
                ->select(['delivered', 'returned', 'gratis'])
                ->where('company_id', $transaction->company_id)
                ->where('bread_type_id', $transaction->bread_type_id)
                ->where('transaction_date', $transaction->transaction_date)
                ->first();

            if ($originalTransaction) {
                $transaction->originalValues = [
                    'delivered' => $originalTransaction->delivered,
                    'returned' => $originalTransaction->returned,
                    'gratis' => $originalTransaction->gratis ?? 0
                ];
            }
        });

        // Updated event
        static::updated(function ($transaction) {
            if (!self::shouldTrackChanges()) {
                return;
            }

            try {
                // Only track relevant changes
                $changedFields = array_intersect_key(
                    $transaction->getChanges(), 
                    array_flip(['delivered', 'returned', 'gratis'])
                );
                
                if (!empty($changedFields)) {
                    static::createHistoryRecord(
                        $transaction, 
                        $transaction->originalValues ?? [], 
                        $changedFields,
                        'update'
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to record update history: ' . $e->getMessage(), [
                    'transaction_id' => $transaction->id
                ]);
            }
        });
    }

  /**
 * Determine if changes should be tracked based on current time
 * Only track between 12:00 PM (noon) and 05:00 AM the next day
 * 
 * Using a static variable as cache to avoid multiple time calculations per request
 */
protected static function shouldTrackChanges()
{
    static $shouldTrack = null;
    
    if ($shouldTrack === null) {
        $currentHour = now()->hour;
        
        // Track if time is between 12:00 PM (hour 12) and 05:00 AM (hour 5)
        // This means either:
        // - Current hour is noon or later (12-23)
        // - Current hour is early morning (0-4)
        $shouldTrack = ($currentHour >= 12) || ($currentHour < 5);
    }
    
    return $shouldTrack;
}

/**
 * Create a transaction history record only if significant changes were made
 */
protected static function createHistoryRecord($transaction, $oldValues, $newValues, $action = 'update')
{
    // Skip recording for non-significant changes
    if ($action === 'update') {
        $hasSignificantChanges = false;
        
        $fieldsToCheck = ['delivered', 'returned', 'gratis'];
        foreach ($fieldsToCheck as $field) {
            // Check if the field exists in both old and new values
            if (isset($newValues[$field]) && isset($oldValues[$field])) {
                // Only record if there's an actual change (different values)
                if ($newValues[$field] != $oldValues[$field]) {
                    // Further filter out zero-to-zero changes
                    if (!($newValues[$field] == 0 && $oldValues[$field] == 0)) {
                        $hasSignificantChanges = true;
                        break;
                    }
                }
            } elseif (isset($newValues[$field]) && !isset($oldValues[$field]) && $newValues[$field] != 0) {
                // If new field added with non-zero value
                $hasSignificantChanges = true;
                break;
            }
        }
        
        if (!$hasSignificantChanges) {
            return null;
        }
    }

    // Create filtered new_values and old_values arrays that only contain the fields that actually changed
    $filteredNewValues = [];
    $filteredOldValues = [];
    
    foreach ($fieldsToCheck as $field) {
        if (isset($newValues[$field]) && isset($oldValues[$field]) && $newValues[$field] != $oldValues[$field]) {
            $filteredNewValues[$field] = $newValues[$field];
            $filteredOldValues[$field] = $oldValues[$field];
        }
    }

    // Use create with only the needed fields to reduce query size
    return TransactionHistory::create([
        'transaction_id' => $transaction->id,
        'user_id' => auth()->id(),
        'action' => $action,
        'old_values' => $filteredOldValues,
        'new_values' => $filteredNewValues,
        'ip_address' => request()->ip()
    ]);
}

//     /**
//      * Create a transaction history record only if significant changes were made
//      */
//     protected static function createHistoryRecord($transaction, $oldValues, $newValues, $action = 'update')
//     {
//         // Skip recording for non-significant changes
//         if ($action === 'update') {
//             $hasSignificantChanges = false;
            
//             $fieldsToCheck = ['delivered', 'returned', 'gratis'];
//             foreach ($fieldsToCheck as $field) {
//                 // Check if the field changed and the change is significant (more than 1)
//                 if (isset($newValues[$field]) && isset($oldValues[$field]) && 
//                     abs($newValues[$field] - $oldValues[$field]) > 1) {
//                     $hasSignificantChanges = true;
//                     break;
//                 }
//             }
            
//             if (!$hasSignificantChanges) {
//                 return null;
//             }
//         }

//         // Use create with only the needed fields to reduce query size
//         return TransactionHistory::create([
//             'transaction_id' => $transaction->id,
//             'user_id' => auth()->id(),
//             'action' => $action,
//             'old_values' => $oldValues,
//             'new_values' => $newValues,
//             'ip_address' => request()->ip()
//         ]);
//     }
// }
}