<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'binance_added_at',
    ];

    protected $casts = [
        'binance_added_at' => 'timestamp',
    ];

    public function baseCoin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coin::class, 'base_coin_id');
    }

    public function quoteCoin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coin::class, 'quote_coin_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }
}
