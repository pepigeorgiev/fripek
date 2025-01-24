<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyTransaction;
use Illuminate\Support\Facades\DB;

class ClearDailyTransactions extends Command
{
    protected $signature = 'daily-transactions:clear';
    protected $description = 'Clear all daily transactions while keeping companies and bread types';

    public function handle()
    {
        if ($this->confirm('Are you sure you want to clear all daily transactions? This cannot be undone.')) {
            try {
                // Count transactions before deletion
                $countBefore = DailyTransaction::count();
                $this->info("Found {$countBefore} transactions to delete.");

                DB::beginTransaction();
                
                // Delete all daily transactions
                DailyTransaction::truncate();
                
                DB::commit();
                
                // Count transactions after deletion to verify
                $countAfter = DailyTransaction::count();
                
                $this->info('Successfully cleared all daily transactions.');
                $this->info("Deleted {$countBefore} transactions.");
                $this->info("Remaining transactions: {$countAfter}");

                if ($countAfter > 0) {
                    $this->warn('Warning: There are still some transactions in the database!');
                } else {
                    $this->info('Database is clean - all transactions have been removed.');
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error('Error clearing daily transactions: ' . $e->getMessage());
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }
        }
    }
}