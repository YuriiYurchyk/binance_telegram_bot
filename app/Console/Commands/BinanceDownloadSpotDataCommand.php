<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use App\Services\BinanceDownloadHDataHandler;
use Illuminate\Database\Eloquent\Collection;
use App\Models\HandledFiles;
use App\Jobs\DownloadBinanceData;
use App\Services\BinanceLinkHelper;
use App\Enum\BinanceDailyVO;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Enum\BinanceMonthlyVO;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    public function handle()
    {
        $this->downloadQueue();
        $this->importQueue();

        return;

        $handler = new BinanceDownloadHDataHandler();

        $initStartDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $initEndDate = Carbon::now()->setYear(2022)->setMonth(05)->setDay(22);

        /** @var Collection<int, TradingPair> $tradingPairs */

        //        $tp = Coin::where('google_alerts', 1)->tradingPairsBaseCoin();

        $tradingPairs = TradingPair::orderBy('id')->get();

        foreach ($tradingPairs as $pair) {
            $startDate = $pair->binance_added_at?->clone() ?: $initStartDate->clone();
            $endDate = $pair->binance_removed_at?->clone() ?: $initEndDate->clone();

            $handler->setTradingPair($pair);
            $handler->handlePeriod($startDate, $endDate);
        }

        return 0;
    }

    private function downloadQueue()
    {
        $helper = new BinanceLinkHelper();
        $helper->setPeriod((new BinanceDailyVO()));

        HandledFiles::getMonthlyQuery()
                    ->each(function (HandledFiles $handledFile) use ($helper) {
                        $helper->setTradingPair($handledFile->tradingPair);

                        $destinationPath = $handledFile->getDestinationPath();
                        $fileNameNoExt = $handledFile->getFilename(null);
                        $url = $handledFile->getBinanceFileUrl();

                        DownloadBinanceData::dispatch($url, $destinationPath, $fileNameNoExt, $handledFile->tradingPair)
                                           ->onQueue('download');
                    });
    }

    private function importQueue()
    {
        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceMonthlyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $monthlyHandledFiles = $q->get();
        $monthlyHandledFiles->each(function (HandledFiles $handledFile) {
            ImportBinanceHistoryDataJob::dispatch($handledFile->getFullPath('.csv'), $handledFile->tradingPair->id)
                                       ->onQueue('import');
        });


        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceDailyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $q->whereDoesntHave('monthlyFile');
        $dailyHandledFiles = $q->get();
        $dailyHandledFiles->each(function (HandledFiles $handledFile) {
            ImportBinanceHistoryDataJob::dispatch($handledFile->getFullPath('.csv'), $handledFile->tradingPair->id)
                                       ->onQueue('import');
        });
    }
}
