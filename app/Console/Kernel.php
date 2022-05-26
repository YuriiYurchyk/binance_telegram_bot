<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('parser-news:binance')->everyMinute();
        $schedule->command('google:parse-alerts')->everyTenMinutes();
        $schedule->command('binance:update-coin-list')->dailyAt('04:00');

        $schedule->command('queue:work --max-time=300')
                 ->everyFiveMinutes();

        //        $schedule->command('telegram-bot:handle-messages')->everyMinute();
        //        $schedule->command('bot:run')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
