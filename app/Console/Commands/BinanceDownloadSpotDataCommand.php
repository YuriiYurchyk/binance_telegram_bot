<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Models\HandledFiles;
use App\Services\BinanceHistoricalDataDownloaderService;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    private TradingPair $currentPair;

    private BinanceHistoricalDataDownloaderService $monthlyDownloaderService;
    private BinanceHistoricalDataDownloaderService $dailyDownloaderService;

    public function __construct()
    {
        parent::__construct();

        $this->monthlyDownloaderService = BinanceHistoricalDataDownloaderService::makeMonthlyDownloader();
        $this->dailyDownloaderService = BinanceHistoricalDataDownloaderService::makeDailyDownloader();
    }

    public function handle()
    {
        $startDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $endDate = Carbon::now()->setYear(2022)->setMonth(05)->setDay(22);

        $tradingPairs = TradingPair::orderBy('id')->get();
        foreach ($tradingPairs as $pair) {
            $this->currentPair = $pair;

            $this->monthlyDownloaderService->setTradingPair($pair);
            $this->dailyDownloaderService->setTradingPair($pair);

            $startDate = $this->currentPair->binance_added_at?->clone() ?: $startDate;
            $endDate = $this->currentPair->binance_removed_at?->clone() ?: $endDate;

            $this->handlePeriod($startDate, $endDate);
        }

        return 0;
    }

    private function handlePeriod($currentDate, $endDate)
    {
        /** @var Carbon $currentDate */
        while ($currentDate->lt($endDate)) {
            $this->monthlyDownloaderService->setDate($currentDate);

//            if ($currentDate->isCurrentMonth()) {
//                $this->handleMonthByDay($currentDate);
//            } else {
//                $status = $this->monthlyDownloaderService->handle();
//                if (BinanceHistoricalDataDownloaderService::STATUS_REMOTE_FILE_NOT_FOUND === $status) {
//                    $this->handleMonthByDay($currentDate);
//                }
//            }

//            $this->importInDb();

            $this->compressFile();
            $currentDate->addMonth();
        }
    }

    private function handleMonthByDay(Carbon $month)
    {
        $startDate = $month->clone()->startOfMonth();

        $endDate = match ($month->isCurrentMonth()) {
            true => Carbon::now()->subDays(3),
            false => $month->clone()->endOfMonth(),
        };

        while ($startDate->lt($endDate)) {
            $this->dailyDownloaderService->setDate($startDate);
            $this->dailyDownloaderService->handle();

            $startDate->addDay();
        }
    }

    private function importInDb()
    {
        $csvFullPathWithExt = $this->monthlyDownloaderService->getFilenameFullPath('.csv');
        if (!file_exists($csvFullPathWithExt)) {
            return false;
        }

        $onlyFileNameWithExt = $this->monthlyDownloaderService->getFilename('.csv');
        if (HandledFiles::where('file_name', $onlyFileNameWithExt)->exists()) {
            return false;
        }

        var_dump($csvFullPathWithExt);
        ImportBinanceHistoryDataJob::dispatch($csvFullPathWithExt, $this->currentPair->id);

        return true;
    }

    private function compressFile()
    {
        $csvFullPath = $this->monthlyDownloaderService->getFilenameFullPath('.csv');
        $csvGzFullPath = $this->monthlyDownloaderService->getFilenameFullPath('.csv.gz');
        $zipFullPath = $this->monthlyDownloaderService->getFilenameFullPath('.zip');

        //        if (file_exists($zipFullPath)) {
        //            unlink($zipFullPath);
        //            var_dump('unlink ' . $zipFullPath);
        //        }

        $csvGzExists = file_exists($csvGzFullPath);
        $csvExists = file_exists($csvFullPath);

        if ($csvGzExists && $csvExists) {
            unlink($csvFullPath);
            var_dump('unlink ' . $csvFullPath);

            return true;
        }

        if ($csvGzExists) {
            return true;
        }

        if (!$csvExists) {
            return true;
        }

        var_dump('compress ' . $csvFullPath);
        copy($csvFullPath, 'compress.zlib:///' . trim($csvGzFullPath, '/'));
        unlink($csvFullPath);
    }

}
