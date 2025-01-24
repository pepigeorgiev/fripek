<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\TransactionHistory;

trait TracksHistory
{
    protected static function bootTracksHistory()
    {
        Log::info('TracksHistory trait booted');

        static::creating(function ($transaction) {
            // Check for existing transaction
            $existingTransaction = \DB::table('daily_transactions')
                ->where('company_id', $transaction->company_id)
                ->where('bread_type_id', $transaction->bread_type_id)
                ->where('transaction_date', $transaction->transaction_date)
                ->first();

            if ($existingTransaction) {
                $transaction->originalValues = [
                    'delivered' => $existingTransaction->delivered,
                    'returned' => $existingTransaction->returned,
                    'gratis' => $existingTransaction->gratis ?? 0
                ];
                
                Log::info('Found existing transaction values', [
                    'existing_values' => $transaction->originalValues
                ]);
            }
        });

        static::created(function ($transaction) {
            try {
                $newValues = $transaction->getAttributes();
                
                Log::info('Transaction created', [
                    'transaction_id' => $transaction->id,
                    'has_original_values' => isset($transaction->originalValues),
                    'new_values' => $newValues
                ]);

                $historyRecord = static::createHistoryRecord(
                    $transaction, 
                    $transaction->originalValues ?? [], 
                    $newValues,
                    isset($transaction->originalValues) ? 'update' : 'create'
                );
            } catch (\Exception $e) {
                Log::error('Failed to record create history', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id
                ]);
            }
        });

        // Keep the existing update handlers
        static::updating(function ($transaction) {
            // For updates, get the original values from the database
            $originalTransaction = \DB::table('daily_transactions')
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
                
                Log::info('Found original values before update', [
                    'transaction_id' => $transaction->id,
                    'original_values' => $transaction->originalValues
                ]);
            }
        });

        static::updated(function ($transaction) {
            try {
                $changes = $transaction->getChanges();
                $oldValues = $transaction->originalValues ?? [];
                
                Log::info('Update detected', [
                    'transaction_id' => $transaction->id,
                    'old_values' => $oldValues,
                    'new_values' => $changes
                ]);

                if (!empty($changes)) {
                    $historyRecord = static::createHistoryRecord(
                        $transaction, 
                        $oldValues, 
                        $changes,
                        'update'
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to record update history', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id
                ]);
            }
        });
    }

    protected static function createHistoryRecord($transaction, $oldValues, $newValues, $action = 'update')
    {
        // If we have old values, it's an overwrite
        $isOverwrite = !empty($oldValues);

        Log::info('Creating history record', [
            'transaction_id' => $transaction->id,
            'action' => $action,
            'is_overwrite' => $isOverwrite,
            'old_values' => $oldValues,
            'new_values' => $newValues
        ]);

        return TransactionHistory::create([
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip()
        ]);
    }

    // Keep existing recordHistory method for backward compatibility
    public function recordHistory($transaction, $oldValues, $newValues)
    {
        return static::createHistoryRecord($transaction, $oldValues, $newValues);
    }
} 