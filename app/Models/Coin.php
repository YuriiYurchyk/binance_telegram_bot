<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function baseCoins()
    {
        return $this->belongsToMany(
            self::class,
            table: 'trading_pairs',
            foreignPivotKey: 'quote_coin_id',
            relatedPivotKey: 'base_coin_id',
            parentKey: 'id',
            relatedKey: 'id',
        )->withTimestamps();
    }

    public function quoteCoins()
    {
        return $this->belongsToMany(
            self::class,
            table: 'trading_pairs',
            foreignPivotKey: 'base_coin_id',
            relatedPivotKey: 'quote_coin_id',
            parentKey: 'id',
            relatedKey: 'id',
        )->withTimestamps()->withPivot('status', 'binance_added_at');
    }
}
