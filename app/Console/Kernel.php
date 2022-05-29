<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ParseBinanceCoinListCommand;
use App\Console\Commands\GoogleAlertsParserCommand;
use App\Console\Commands\NewsBinanceParserCommand;

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
        $schedule->command(NewsBinanceParserCommand::class)->everyMinute();
        $schedule->command(GoogleAlertsParserCommand::class)->everyTenMinutes();
        $schedule->command(ParseBinanceCoinListCommand::class)->dailyAt('04:00');

        $schedule->command('queue:work', ['--stop-when-empty'])->everyMinute();

        //        $schedule->command('telegram-bot:handle-messages')->everyMinute();
        //        $schedule->command('bot:run')->everyMinute();

        $schedule->command('log:delete')->daily();
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
