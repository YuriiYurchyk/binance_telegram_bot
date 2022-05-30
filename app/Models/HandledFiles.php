<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Enum\BinanceMonthlyVO;
use Carbon\Carbon;

class HandledFiles extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'handled_success',
        'period',
        'file_exists_on_binance',
    ];

    private string $basePath = '/var/www/ssd'; // '/var/www'

    private const      DATA_RANGE = "1m";

    public function dailyFiles(): HasMany
    {
        return $this->hasMany(self::class, 'monthly_file_id');
    }

    public function monthlyFile(): BelongsTo
    {
        return $this->belongsTo(self::class, 'monthly_file_id');
    }

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class, 'trading_pair_id');
    }

    public function getBinanceFileUrl()
    {
        $fileNameZip = $this->getFilename('.zip');
        $pairName = $this->tradingPair->getTradingSpotPairCode();
        $dataRange = self::DATA_RANGE;

        $periodName = match ($this->period) {
            (new BinanceMonthlyVO)->getCode() => (new BinanceMonthlyVO)->getValue(),
            (new BinanceDailyVO())->getCode() => (new BinanceDailyVO)->getValue(),
        };

        return "https://data.binance.vision/data/spot/$periodName/klines/$pairName/$dataRange/$fileNameZip";
    }

    public function getFilename(?string $ext): string
    {
        $fileNameNoExt = str_replace('.csv', '', $this->file_name);

        return $fileNameNoExt . $ext;
    }

    public function getFilenameFullPath(string $ext)
    {
        return $this->getDestinationPath() . '/' . $this->getFilename($ext);
    }

    public function getDestinationPath(): ?string
    {
        if (!$this->tradingPair) {
            [$pairCode] = explode('-', $this->file_name);
            $tradingPair = TradingPair::where('pair_code', $pairCode)->first();
            if (!$tradingPair) {
                throw new \Exception("Related trading pair not found . HandledFile recordId = $this->id");
            }

            $this->tradingPair()->associate($tradingPair)->save();
            $this->save();
        }

        $pairName = $this->tradingPair->getTradingSpotPairCode();

        return rtrim($this->getBasePath(), '/') . '/' . "binance-data/monthly/$pairName";
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public static function scopePeriod(Builder $q, BinancePeriodVO $period): Builder
    {
        return $q->where('period', $period->getCode());
    }

    public static function scopeFileExistsOnBinance(Builder $q, bool $status = true): Builder
    {
        return $q->where('file_exists_on_binance', (int) $status);
    }

    public function setPeriod(BinancePeriodVO $period): static
    {
        $this->period = $period->getCode();

        return $this;
    }

    public static function createModel(TradingPair $tradingPair, BinancePeriodVO $period, Carbon $date): static
    {
        $fileName = self::generateFilename($tradingPair, $period, $date, '.csv');

        $model = new static();
        $model->file_name = $fileName;
        $model->handled_success = 0;
        $model->file_exists_on_binance = 1;
        $model->setPeriod($period);
        $model->tradingPair()->associate($tradingPair)->save();
        $model->save();

        return $model;
    }

    public static function generateFilename(
        TradingPair $tradingPair,
        BinancePeriodVO $period,
        Carbon $date,
        string $ext = null
    ): string {
        $pairName = $tradingPair->getTradingSpotPairCode();
        $dateFormatted = self::getFormattedDate($period, $date);

        return "$pairName-" . self::DATA_RANGE . "-{$dateFormatted}{$ext}";
    }

    private static function getFormattedDate(BinancePeriodVO $period, Carbon $date): string
    {
        $format = match (get_class($period)) {
            BinanceDailyVO::class => 'Y-m-d',
            BinanceMonthlyVO::class => 'Y-m',
        };

        return $date->format($format);
    }

    public function isFileAlreadyDownloaded(): bool
    {
        $csvPath = $this->getFilenameFullPath('.csv');
        $csvGzPath = $this->getFilenameFullPath('.csv.gz');
        $zipPath = $this->getFilenameFullPath('.zip');

        return file_exists($csvPath)
            || file_exists($csvGzPath);
    }
}
