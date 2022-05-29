<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\BinancePeriodVO;

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
        $pairName = $this->tradingPair->getTradingSpotPairCode();;

        return "https://data.binance.vision/data/spot/$this->period/klines/$pairName/$this->dataRange/$fileNameZip";
    }

    public function getFilename(?string $ext): string
    {
        $fileNameNoExt = str_replace('.csv', '', $this->file_name);

        return $fileNameNoExt . $ext;
    }

    public function getFullPath(string $ext)
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

            $this->tradingPair()->associate($tradingPair);
            $this->save();
        }

        $pairName = $this->tradingPair->getTradingSpotPairCode();

        return rtrim($this->getBasePath(), '/') . '/' . "binance - data / monthly / $pairName";
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
        return $q->where('file_exists_on_binance', (int)$status);
    }

    public function setPeriod(BinancePeriodVO $period): static
    {
        $this->period = $period->getCode();

        return $this;
    }
}
