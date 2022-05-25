<?php

namespace App\Services;

use Carbon\Carbon;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Models\HandledFiles;
use App\Models\TradingPair;

class BinanceDownloadHDataHandler
{
    private BinanceDownloadHDataService $monthlyDownloaderService;
    private BinanceDownloadHDataService $dailyDownloaderService;

    private TradingPair $tradingPair;

    public function __construct()
    {
        $this->monthlyDownloaderService = BinanceDownloadHDataService::makeMonthlyDownloader();
        $this->dailyDownloaderService = BinanceDownloadHDataService::makeDailyDownloader();
    }

    public function setTradingPair(TradingPair $tradingPair): static
    {
        $this->tradingPair = $tradingPair;
        $this->monthlyDownloaderService->setTradingPair($tradingPair);
        $this->dailyDownloaderService->setTradingPair($tradingPair);

        return $this;
    }

    public function handlePeriod(Carbon $startDate, Carbon $endDate)
    {
        while ($startDate->lte($endDate)) {
            $this->handleDate($startDate);
            $startDate->addMonth();
        }
    }

    private function handleDate(Carbon $date)
    {
        $this->monthlyDownloaderService->setDate($date);

        if ($date->isCurrentMonth()) {
            $this->handleMonthByDay($date);
        } else {
            $status = $this->monthlyDownloaderService->handle();
            if (BinanceDownloadHDataService::STATUS_REMOTE_FILE_NOT_FOUND === $status) {
                $this->handleMonthByDay($date);
            }
        }
        //            $this->importInDb();

        //        $this->compressFile();
    }

    private function handleMonthByDay(Carbon $month)
    {
        $startDate = $month->clone()->startOfMonth();

        $endDate = match ($month->isCurrentMonth()) {
            true => Carbon::now()->subDays(1),
            false => $month->clone()->endOfMonth(),
        };

        while ($startDate->lte($endDate)) {
            $this->dailyDownloaderService->setDate($startDate);
            $s = $this->dailyDownloaderService->handle();

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
        ImportBinanceHistoryDataJob::dispatch($csvFullPathWithExt, $this->tradingPair->id);

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