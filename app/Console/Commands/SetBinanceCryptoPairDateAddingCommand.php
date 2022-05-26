<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingPair;
use Carbon\Carbon;

class SetBinanceCryptoPairDateAddingCommand extends Command
{
    protected $signature = 'crypto-pairs:set-added-date';

    protected $description = 'Command description';

    public function handle()
    {
        /** @var TradingPair[] $tradePairs */
        $tradePairs = TradingPair::get();
        foreach ($tradePairs as $tradePair) {
            if (1 == $tradePair->status) {
                $tradePair->binance_removed_at = null;
                $tradePair->save();
                continue;
            }
            $lastOrderCloseTime = $tradePair->binanceSpotHistory()
                                            ->orderByDesc('open_time')
                                            ->first();

            if (!$lastOrderCloseTime) {
                var_dump($tradePair->id);
            }

            $lastOrderCloseTime = $lastOrderCloseTime?->close_time;
            if (!$lastOrderCloseTime) {
                continue;
            }

            $lastOrderCloseTimeC = Carbon::createFromTimestampMsUTC($lastOrderCloseTime);
            $lastOrderCloseTimeC->setTimezone(config('app.timezone'));
            $tradePair->binance_removed_at = $lastOrderCloseTimeC;

            $tradePair->save();
        }

        /** @var TradingPair[] $tradePairs */
        $tradePairs = TradingPair::whereHas('binanceSpotHistory')->get();
        foreach ($tradePairs as $tradePair) {
            $firstOrderOpenTime = $tradePair->binanceSpotHistory()
                                            ->orderBy('open_time')
                                            ->first()->open_time;
            $firstOrderOpenTimeC = Carbon::createFromTimestampMsUTC($firstOrderOpenTime);
            $firstOrderOpenTimeC->setTimezone(config('app.timezone'));
            $tradePair->binance_added_at = $firstOrderOpenTimeC;

            $tradePair->save();
        }

        return 0;
    }
}
