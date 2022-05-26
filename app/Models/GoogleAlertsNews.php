<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GoogleAlertsNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'content',
        'news_published_at',
        'news_updated_at',
    ];

    protected $casts = [
        'news_published_at' => 'datetime',
        'news_updated_at' => 'datetime',
    ];

    protected function asDateTime($value)
    {
        if ($value instanceof Carbon) {
            $value->timezone(config('app.timezone'));
        }

        return parent::asDateTime($value);
    }

    public function coins()
    {
        return $this->belongsToMany(
            Coin::class,
            'coins_google_alerts_news',
            'google_alerts_news_id',
            'coins_id',
        );
    }
}
