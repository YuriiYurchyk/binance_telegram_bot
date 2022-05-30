<?php declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use App\Models\TradingPair;
use App\Models\HandledFiles;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Enum\BinanceMonthlyVO;

class BinanceDownloadHDataService
{
    protected Carbon $date;

    private TradingPair $tradingPair;

    private function __construct(private BinancePeriodVO $period)
    {

    }

    public static function makeDailyDownloader(): self
    {
        return new self(new BinanceDailyVO());
    }

    public static function makeMonthlyDownloader(): self
    {
        return new self(new BinanceMonthlyVO());
    }

    public function setDate(Carbon $date): self
    {
        $this->date = clone $date;

        return $this;
    }

    public function setTradingPair(TradingPair $tradingPair): self
    {
        $this->tradingPair = $tradingPair;

        return $this;
    }

    public function createHandledFileIfNotExists(HandledFiles $monthlyHandledFile = null): HandledFiles
    {
        $fileNameCsv = HandledFiles::generateFilename($this->tradingPair, $this->period, $this->date, '.csv');

        $handledFile = HandledFiles::where('file_name', $fileNameCsv)->first();
        if (empty($handledFile)) {
            $handledFile = HandledFiles::createModel($this->tradingPair, $this->period, $this->date);
        }
        if ($monthlyHandledFile) {
            $monthlyHandledFile->dailyFiles()->save($handledFile);
        }

        return $handledFile;
    }

}