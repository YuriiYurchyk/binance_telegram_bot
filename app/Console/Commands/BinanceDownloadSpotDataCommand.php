<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use App\Services\BinanceDownloadHDataHandler;
use Illuminate\Database\Eloquent\Collection;
use App\Models\HandledFiles;
use App\Jobs\DownloadBinanceData;
use App\Enum\BinanceDailyVO;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Enum\BinanceMonthlyVO;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    public function handle()
    {
        //        $this->createHandledFiles();

        //        $this->downloadQueue();

        $this->importQueue();

        return 0;
    }

    private function createHandledFiles()
    {
        $handler = new BinanceDownloadHDataHandler();

        $initStartDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $initEndDate = Carbon::now()->subDay();

        /** @var Collection<int, TradingPair> $tradingPairs */

        //        $tp = Coin::where('google_alerts', 1)->tradingPairsBaseCoin();
        $tradingPairs = TradingPair::orderBy('id')->get();

        foreach ($tradingPairs as $pair) {
            $startDate = $pair->binance_added_at->clone();
            $endDate = $pair->binance_removed_at?->clone() ?: $initEndDate->clone();

            $handler->setTradingPair($pair);
            $handler->handlePeriod($startDate, $endDate);
        }
    }

    private function downloadQueue()
    {
        HandledFiles::scopePeriod(HandledFiles::query(), new BinanceMonthlyVO())
                    ->where('file_exists_on_binance', 1)
                    ->where('handled_success', 0)
                    ->eachById(function (HandledFiles $handledFile) {
                        if ($handledFile->isFileAlreadyDownloaded()) {
                            return;
                        }

                        DownloadBinanceData::dispatch($handledFile->id)->onQueue('download');
                    });

        HandledFiles::scopePeriod(HandledFiles::query(), new BinanceDailyVO())
                    ->where('file_exists_on_binance', 1)
                    ->where('handled_success', 0)
                    ->whereHas('monthlyFile', function ($q) {
                        $q->where('file_exists_on_binance', 0);
                    })
                    ->eachById(function (HandledFiles $handledFile) {
                        if ($handledFile->isFileAlreadyDownloaded()) {
                            return;
                        }

                        DownloadBinanceData::dispatch($handledFile->id)->onQueue('download');
                    });
    }

    private function importQueue()
    {
        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceMonthlyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $q->eachById(function (HandledFiles $handledFile) {
            ImportBinanceHistoryDataJob::dispatch($handledFile->id)->onQueue('import');
        });

        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceDailyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $q->whereHas('monthlyFile', function ($q) {
            HandledFiles::scopeFileExistsOnBinance($q, false);
        });
        $q->eachById(function (HandledFiles $handledFile) {
            ImportBinanceHistoryDataJob::dispatch($handledFile->id)->onQueue('import');
        });
    }
}
