<?php declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use App\Models\TradingPair;
use App\Jobs\DownloadBinanceData;
use Cache;

class BinanceDownloadHDataService
{
    public const        STATUS_FILE_ALREADY_DOWNLOADED = 1;
    public const        STATUS_REMOTE_FILE_NOT_FOUND   = 404;
    public const        STATUS_FILE_WILL_BE_DOWNLOADED = 3;

    private const DATA_RANGE_DAILY   = 'daily';
    private const DATA_RANGE_MONTHLY = 'monthly';

    protected Carbon $date;

    private TradingPair $tradingPair;
    private string      $dataRange = "1m";

    private string $basePath = '/var/www/ssd'; // '/var/www'

    private function __construct(private string $period)
    {
    }

    public static function makeDailyDownloader(): self
    {
        return new self(self::DATA_RANGE_DAILY);
    }

    public static function makeMonthlyDownloader(): self
    {
        return new self(self::DATA_RANGE_MONTHLY);
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

    private function getDownloadUrl(): string
    {
        $fileNameZip = $this->getFilename('.zip');
        $pairName = $this->tradingPair->getTradingSpotPairCode();;

        return "https://data.binance.vision/data/spot/$this->period/klines/$pairName/$this->dataRange/$fileNameZip";
    }

    public function isRemoteFileNotFound(): bool
    {
        return 404 === (int) Cache::get($this->getDownloadUrl());
    }

    public function handle(): int
    {
        return $this->downloadPairData();
    }

    private function downloadPairData(): int
    {
        if ($this->isFileAlreadyDownloaded()) {
            return self::STATUS_FILE_ALREADY_DOWNLOADED;
        }

        if ($this->isRemoteFileNotFound()) {
            return self::STATUS_REMOTE_FILE_NOT_FOUND;
        }

        $url = $this->getDownloadUrl();
        $destinationPath = $this->getDestinationPath();
        $fileNameNoExt = $this->getFilename();

        DownloadBinanceData::dispatch($url, $destinationPath, $fileNameNoExt, $this->tradingPair->id)
                           ->onQueue('download');

        return self::STATUS_FILE_WILL_BE_DOWNLOADED;
    }

    private function getDestinationPath(): string
    {
        $pairName = $this->tradingPair->getTradingSpotPairCode();

        return rtrim($this->basePath, '/') . '/' . "binance-data/monthly/$pairName";
    }

    private function isFileAlreadyDownloaded(): bool
    {
        $csvPath = $this->getFilenameFullPath('.csv');
        $csvGzPath = $this->getFilenameFullPath('.csv.gz');
        $zipPath = $this->getFilenameFullPath('.zip');

        return file_exists($csvPath)
            || file_exists($csvGzPath);
    }

    private function getFormattedDate(): string
    {
        $format = match ($this->period) {
            'daily' => 'Y-m-d',
            'monthly' => 'Y-m',
        };

        return $this->date->format($format);
    }

    public function getFilename(string $ext = null): string
    {
        $pairName = $this->tradingPair->getTradingSpotPairCode();
        $dateFormatted = $this->getFormattedDate();

        return "$pairName-{$this->dataRange}-{$dateFormatted}{$ext}";
    }

    public function getFilenameFullPath(string $ext): string
    {
        $fileName = $this->getFilename($ext);
        $tradingPairName = $this->tradingPair->getTradingSpotPairCode();;
        $fileSubPath = "binance-data/monthly/$tradingPairName/$fileName";

        return rtrim($this->basePath, '/') . '/' . $fileSubPath;
    }
}