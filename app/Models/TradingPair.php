<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Query\Builder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;

class TradingPair extends Model
{
    use DefaultDatetimeFormat;
    use HasFactory;

    protected $fillable = [
        'base_coin',
        'quote_coin',
        'status',
        'binance_added_at',
        'binance_removed_at',
    ];

    protected $casts = [
        'binance_added_at' => 'datetime',
        'binance_removed_at' => 'datetime',
    ];

    public function baseCoinRel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coin::class, 'base_coin', 'name',);
    }

    public function quoteCoinRel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coin::class, 'quote_coin', 'name',);
    }

    public function parsedNews()
    {
        return $this->belongsToMany(
            ParsedNews::class,
            'parsed_news_trading_pair',
            'trading_pair_id',
            'parsed_news_id',
        )->where('published_date', '>', (string) now()->startOfYear()->setYear(2020));
    }

    public function binanceSpotHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BinanceSpotHistory::class, 'trading_pair_id', 'id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function getTradingPairCode(): string
    {
        return $this->base_coin . '/' . $this->quote_coin;
    }

    public function getTradingSpotPairCode(): string
    {
        return $this->base_coin . $this->quote_coin;
    }

    public static function scopeActiveByPeriod(\Illuminate\Database\Eloquent\Builder $q, CarbonPeriod $period)
    {
        return $q->where('binance_added_at', '<', (string) $period->getStartDate())
                 ->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($period) {
                     $q->whereNull('binance_removed_at');

                     return $q->orWhere('binance_removed_at', '>', (string) $period->getEndDate());
                 });
    }

    public function getPriceByDateTime(Carbon $dateTime): float
    {
        $timestamp = $dateTime->clone()->getTimestampMs();
        $timestamp2 = $dateTime->clone()->addMinutes(2)->getTimestampMs();

        $price = $this->binanceSpotHistory()
                      ->orderBy('open_time')
                      ->where('open_time', '>=', $timestamp)
                      ->where('open_time', '<=', $timestamp2)
                      ->first();

        return $price->open;
    }

    public function getMaxPriceByPeriod(Carbon $date1, Carbon $date2): float
    {
        $timestamp = $date1->clone()->getTimestampMs();
        $timestamp2 = $date2->clone()->getTimestampMs();
        $price = $this->binanceSpotHistory()
                      ->orderByDesc('high')
                      ->where('open_time', '>=', $timestamp)
                      ->where('open_time', '<=', $timestamp2)
                      ->first();

        return $price->high;
    }

    /**
     * @param  Carbon  $datatime
     *
     * @return Collection<int, TradingPair>
     */
    public function getAvailablePairsByDate(Carbon $datatime): Collection
    {
        $q = self::query();
        $coins = [];

        $countBaseCoin = self::query()->where('base_coin', $this->base_coin)
                             ->orWhere('quote_coin', $this->base_coin)->count();
        if ($countBaseCoin < 9) {
            $coins[] = $this->base_coin;
        }

        $countQuoteCoin = self::query()->where('base_coin', $this->quote_coin)
                              ->orWhere('quote_coin', $this->quote_coin)->count();
        if ($countQuoteCoin < 9) {
            $coins[] = $this->quote_coin;
        }

        $q
            ->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($coins) {
                $q->whereIn('base_coin', $coins)
                  ->orWhereIn('quote_coin', $coins);
            })
            ->whereNot('id', $this->id)
            ->where('binance_added_at', '<=', (string) $datatime)
            ->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($datatime) {
                $q->where('binance_removed_at', '>=', (string) $datatime);
                $q->orWhereNull('binance_removed_at');
            });

        return $q->get();
    }


}
