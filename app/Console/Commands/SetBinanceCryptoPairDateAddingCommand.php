<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingPair;

class SetBinanceCryptoPairDateAddingCommand extends Command
{
    protected $signature = 'crypto-pairs:set-added-date';

    protected $description = 'Command description';

    public function handle()
    {
        /** @var TradingPair[] $tradePairs */
        $tradePairs = TradingPair::whereHas('parsedNews')->whereHas('binanceSpotHistory')->get();
        foreach ($tradePairs as $tradePair) {
            $firstOrderOpenTime = $tradePair->binanceSpotHistory()->orderBy('open_time')->first()->open_time;

            $firstOrderOpenTimeC = \Carbon\Carbon::createFromTimestampMsUTC($firstOrderOpenTime);
            $firstOrderOpenTimeC->setTimezone(config('app.timezone'));
            $tradePair->binance_added_at = $firstOrderOpenTimeC;
            $tradePair->save();
        }


        return 0;
    }
}
