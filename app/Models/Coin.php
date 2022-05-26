<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'google_alerts',
        'google_alerts_url',
    ];

    public function baseCoins()
    {
        return $this->belongsToMany(
            self::class,
            table: 'trading_pairs',
            foreignPivotKey: 'quote_coin',
            relatedPivotKey: 'base_coin',
            parentKey: 'name',
            relatedKey: 'name',
        )->withTimestamps()->withPivot('status', 'binance_added_at');
    }

    public function quoteCoins()
    {
        return $this->belongsToMany(
            self::class,
            table: 'trading_pairs',
            foreignPivotKey: 'base_coin',
            relatedPivotKey: 'quote_coin',
            parentKey: 'name',
            relatedKey: 'name',
        )->withTimestamps()->withPivot('status', 'binance_added_at');
    }

    public function tradingPairsBaseCoin(): HasMany
    {
        return $this->hasMany(
            TradingPair::class,
            foreignKey: 'base_coin',
            localKey: 'name',
        );
    }

    public function tradingPairsQuoteCoin(): HasMany
    {
        return $this->hasMany(
            TradingPair::class,
            foreignKey: 'quote_coin',
            localKey: 'name',
        );
    }

    public function googleAlertsNews(): BelongsToMany
    {
        return $this->belongsToMany(
            GoogleAlertsNews::class,
            'coins_google_alerts_news',
            'coins_id',
            'google_alerts_news_id',
        );
    }

    public static function scopeGoogleAlerts(Builder $q)
    {
        return $q->where('google_alerts',true)->whereNotNull('google_alerts_url');
    }
}
