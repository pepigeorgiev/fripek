<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DailyTransaction;
use App\Models\BreadType;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateMonthlySummaries extends Command
{
    protected $signature = 'summaries:generate {company?}';
    protected $description = 'Generate monthly summaries for companies';

    public function handle()
    {
        $companyId = $this->argument('company');

        $companies = $companyId 
            ? Company::where('id', $companyId)->get() 
            : Company::all();

        if ($companies->isEmpty()) {
            $this->error('No companies found!');
            return 1;
        }

        foreach ($companies as $company) {
            $this->generateSummary($company);
        }

        $this->info('Monthly summaries generated successfully!');
    }

    private function generateSummary(Company $company)
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->startOfMonth();

        $this->info("\nGenerating summary for {$company->name}");
        $this->info("Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $breadTypes = BreadType::all();

        foreach ($breadTypes as $breadType) {
            $transactions = DailyTransaction::where('company_id', $company->id)
                ->where('bread_type_id', $breadType->id)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get();

            $delivered = $transactions->sum('delivered');
            $returned = $transactions->sum('returned');
            $total = $delivered - $returned;

            if ($delivered > 0 || $returned > 0) {
                $this->info("\n{$breadType->name}:");
                $this->line("  Delivered: $delivered");
                $this->line("  Returned: $returned");
                $this->line("  Total: $total");
            }
        }
    }
}