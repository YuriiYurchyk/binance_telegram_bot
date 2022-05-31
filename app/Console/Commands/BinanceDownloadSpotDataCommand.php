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
        $this->createHandledFiles();

        $this->downloadAndImportFiles();

        return 0;
    }

    private function createHandledFiles()
    {
        $handler = new BinanceDownloadHDataHandler();

        $initStartDate = Carbon::now()->setYear(2022)->setMonth(05)->setDay(28);
        $initEndDate = Carbon::now()->subDay(); // 29.05.22 processed

        /** @var Collection<int, TradingPair> $tradingPairs */

        //        $tp = Coin::where('google_alerts', 1)->tradingPairsBaseCoin();
        $tradingPairs = TradingPair::orderBy('id')->get();

        foreach ($tradingPairs as $pair) {
            $startDate = $initStartDate; // $pair->binance_added_at->clone();
            $endDate = $pair->binance_removed_at?->clone() ?: $initEndDate->clone();

            $handler->setTradingPair($pair);
            $handler->handlePeriod($startDate, $endDate);
        }
    }

    private function downloadAndImportFiles()
    {
        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceMonthlyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $q->eachById(function (HandledFiles $handledFile) {
            if ($handledFile->isFileAlreadyDownloaded()) {
                ImportBinanceHistoryDataJob::dispatch($handledFile->id)->onQueue('import');

                return;
            }

            DownloadBinanceData::dispatch($handledFile->id)->onQueue('download');
        });

        $q = HandledFiles::query();
        HandledFiles::scopePeriod($q, new BinanceDailyVO());
        HandledFiles::scopeFileExistsOnBinance($q);
        $q->where('handled_success', 0);
        $q->whereHas('monthlyFile', function ($q) {
            $q->where('file_exists_on_binance', 0);
        });
        $q->eachById(function (HandledFiles $handledFile) {
            if ($handledFile->isFileAlreadyDownloaded()) {
                ImportBinanceHistoryDataJob::dispatch($handledFile->id)->onQueue('import');

                return;
            }

            DownloadBinanceData::dispatch($handledFile->id)->onQueue('download');
        });
    }
}
