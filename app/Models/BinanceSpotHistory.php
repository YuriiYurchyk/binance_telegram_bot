<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinanceSpotHistory extends Model
{
    use HasFactory;

    protected $table = 'binance_spot_history';

    protected $fillable = [
        'data_range',
        'open_time',
        'open',
        'high',
        'low',
        'close',
        'close_time',
    ];

    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class, 'trading_pair_id');
    }
}
