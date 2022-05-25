<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use App\Services\BinanceDownloadHDataHandler;
use Illuminate\Database\Eloquent\Collection;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    private TradingPair $currentPair;

    public function handle()
    {
        $handler = new BinanceDownloadHDataHandler();

        $initStartDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $initEndDate = Carbon::now()->setYear(2022)->setMonth(05)->setDay(22);

        /** @var Collection<int, TradingPair> $tradingPairs */

//        $tp = Coin::where('google_alerts', 1)->tradingPairsBaseCoin();

        //        $tradingPairs = TradingPair::orderBy('id')->get();
        $tradingPairs = TradingPair
            ::query()
            ->whereHas('baseCoinRel', function ($q) {
                $q->where('google_alerts', 1);
            })
            ->orWhereHas('quoteCoinRel', function ($q) {
                $q->where('google_alerts', 1);
            })
            ->get();

        foreach ($tradingPairs as $pair) {
            $startDate = $pair->binance_added_at?->clone() ?: $initStartDate->clone();
            $endDate = $pair->binance_removed_at?->clone() ?: $initEndDate->clone();

            $handler->setTradingPair($pair);
            $handler->handlePeriod($startDate, $endDate);
        }

        return 0;
    }


}
