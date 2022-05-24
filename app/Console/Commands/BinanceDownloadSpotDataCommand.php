<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Models\HandledFiles;
use App\Services\BinanceDownloadHDataService;
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

        $startDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $endDate = Carbon::now()->setYear(2022)->setMonth(05)->setDay(22);

        /** @var Collection<int, TradingPair> $tradingPairs */
        $tradingPairs = TradingPair::orderBy('id')->get();
        foreach ($tradingPairs as $pair) {
            $startDate = $pair->binance_added_at?->clone() ?: $startDate;
            $endDate = $pair->binance_removed_at?->clone() ?: $endDate;

            $handler->setTradingPair($pair);
            $handler->handlePeriod($startDate, $endDate);
        }

        return 0;
    }


}
