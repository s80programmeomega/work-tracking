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
        // Task 8c — Daily digest scheduler.
        // Runs every 15 minutes so users with various digest_time settings get
        // their digest within ~15 min of their configured time. The command
        // itself filters users by `digest_time <= now()` and dedups via
        // `last_digest_sent_at`, so repeated runs are safe (no duplicate sends).
        $schedule->command('notifications:send-digest')
            ->everyFifteenMinutes()
            ->withoutOverlapping(20)
            ->onOneServer()
            ->runInBackground();
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
