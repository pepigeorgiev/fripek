<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BreadSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupBreadSales extends Command
{
    protected $signature = 'bread-sales:cleanup {date? : The date to cleanup (YYYY-MM-DD format)}';
    protected $description = 'Clean up duplicate bread sales records for a specific date';

    public function handle()
    {
        try {
            $date = $this->argument('date') ?? now()->toDateString();
            
            // Start transaction
            DB::beginTransaction();
            
            // Get count before deletion
            $count = BreadSale::whereDate('transaction_date', $date)->count();
            
            // Delete all records for the specified date
            BreadSale::whereDate('transaction_date', $date)->delete();
            
            DB::commit();
            
            $this->info("Successfully deleted {$count} records for date {$date}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error cleaning up bread sales: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}