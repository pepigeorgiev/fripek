<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\GenerateMonthlySummaries::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('summaries:generate')->monthly();
        
    }
    protected $commands1 = [
        Commands\ClearDailyTransactions::class,
    ];

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $routeMiddleware = [
        // ...
        'role' => \App\Http\Middleware\CheckRole::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        

    ];
}