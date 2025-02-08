<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncInsurances::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('insurances:sync')->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        // Enregistre les commandes artisan
        require base_path('routes/console.php');
    }
}
