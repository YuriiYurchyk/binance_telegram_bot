<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_coin',
        'quote_coin',
        'status',
        'binance_added_at',
    ];

    protected $casts = [
        'binance_added_at' => 'timestamp',
    ];

    public function baseCoin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coin::class, 'base_coin', 'name',);
    }

    public function quoteCoin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
        )->where('published_date', '>', (string)now()->startOfYear()->setYear(2020));
    }

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function getTradingPairCode(): string
    {
        return $this->baseCoin->name . '/' . $this->quoteCoin->name;
    }


}
