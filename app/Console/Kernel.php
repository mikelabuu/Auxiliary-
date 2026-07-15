<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the expiry check every minute
        $schedule->command('bookings:expire')->everyMinute()->withoutOverlapping();

        // Schedule times are evaluated in the app timezone (UTC) unless pinned;
        // without ->timezone() "00:05" would fire at 08:05 Manila.
        $schedule->command('bookings:mark-no-show')
            ->timezone('Asia/Manila')->dailyAt('00:05');

        // The command no-ops before 2 PM Manila; half-hourly runs give it
        // same-day retries if a run is missed.
        $schedule->command('bookings:autocheckout')
            ->everyThirtyMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
