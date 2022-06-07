<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coin;
use Illuminate\Database\Eloquent\Collection;
use App\Jobs\ParseGoogleAlertsJob;
use Log;

class GoogleAlertsParserCommand extends Command
{
    protected $signature = 'google:parse-alerts';

    protected $description = 'Command description';

    public function handle()
    {
        Log::info('Start handle ' . static::class);

        // далі треба виводити кількість алертів для коїна по кожній годині
        // і так за останні 100. Це можна і динамічно порахувати

        /** @var Collection<int, Coin> $coinsWithAlert */
        $coinsWithAlert = Coin::scopeGoogleAlerts(Coin::query())->get();

        foreach ($coinsWithAlert as $coin) {
            ParseGoogleAlertsJob::dispatch($coin->id)->onQueue('google:parse-alerts');
        }

        return 0;
    }


}
