<?php declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use App\Models\TradingPair;
use App\Models\HandledFiles;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Enum\BinanceMonthlyVO;

class BinanceDownloadHDataHandler
{
    private TradingPair $tradingPair;

    public function setTradingPair(TradingPair $tradingPair): static
    {
        $this->tradingPair = $tradingPair;

        return $this;
    }

    public function handlePeriod(Carbon $startDate, Carbon $endDate): void
    {
        while ($startDate->lte($endDate)) {
            $this->handleDate($startDate);
            $startDate->addMonth();
        }
    }

    private function handleDate(Carbon $date): void
    {
        $monthlyHandledFile = $this->createHandledFileIfNotExists(new BinanceMonthlyVO(), $date);
        $this->handleMonthByDay($date, $monthlyHandledFile);
    }

    private function handleMonthByDay(Carbon $month, HandledFiles $monthlyHandledFile): void
    {
        $startDate = $month->clone()->startOfMonth();

        $endDate = match ($month->isCurrentMonth()) {
            true => Carbon::now()->subDays(1),
            false => $month->clone()->endOfMonth(),
        };

        while ($startDate->lte($endDate)) {
            $this->createHandledFileIfNotExists(new BinanceDailyVO(), $startDate, $monthlyHandledFile);

            $startDate->addDay();
        }
    }

    private function createHandledFileIfNotExists(
        BinancePeriodVO $period,
        Carbon $date,
        HandledFiles $monthlyHandledFile = null
    ): HandledFiles {
        $fileNameCsv = HandledFiles::generateFilename($this->tradingPair, $period, $date, '.csv');

        $handledFile = HandledFiles::where('file_name', $fileNameCsv)->first();
        if (empty($handledFile)) {
            $handledFile = HandledFiles::createModel($this->tradingPair, $period, $date);
        }
        if ($monthlyHandledFile) {
            $monthlyHandledFile->dailyFiles()->save($handledFile);
        }

        return $handledFile;
    }

}