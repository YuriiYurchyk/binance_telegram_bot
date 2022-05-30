<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\TradingPair;
use App\Models\HandledFiles;

class BinanceDownloadHDataHandler
{
    private BinanceDownloadHDataService $monthlyDownloaderService;
    private BinanceDownloadHDataService $dailyDownloaderService;

    public function __construct()
    {
        $this->monthlyDownloaderService = BinanceDownloadHDataService::makeMonthlyDownloader();
        $this->dailyDownloaderService = BinanceDownloadHDataService::makeDailyDownloader();
    }

    public function setTradingPair(TradingPair $tradingPair): static
    {
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
        $monthlyHandledFile = $this->monthlyDownloaderService->createHandledFileIfNotExists();

        $this->handleMonthByDay($date, $monthlyHandledFile);
    }

    private function handleMonthByDay(Carbon $month, HandledFiles $monthlyHandledFile)
    {
        $startDate = $month->clone()->startOfMonth();

        $endDate = match ($month->isCurrentMonth()) {
            true => Carbon::now()->subDays(1),
            false => $month->clone()->endOfMonth(),
        };

        while ($startDate->lte($endDate)) {
            $this->dailyDownloaderService->setDate($startDate);
            $this->dailyDownloaderService->createHandledFileIfNotExists($monthlyHandledFile);

            $startDate->addDay();
        }
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