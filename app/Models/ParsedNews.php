<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Encore\Admin\Traits\DefaultDatetimeFormat;

class ParsedNews extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'title',
        'url',
        'site_about',
        'site_source',
        'published_date',
        'is_new',
    ];

    protected $casts = [
        'published_date' => 'datetime',
    ];

    public static function scopeIsNew(Builder $q): Builder
    {
        return $q->where('is_new', 1);
    }

    public function getForTelegram(): string
    {
        $blocks = [
            "<b>$this->published_date</b>",
            $this->title,
            "About: <a href=\"$this->site_about\">$this->site_about</a> ",
            "Source: <a href=\"$this->site_source\">$this->site_source</a> ",
            "<a href=\"$this->url\">Read Full Version</a>",
        ];

        return implode(PHP_EOL, $blocks);
    }

    public function parsedNews()
    {
        return $this->belongsToMany(
            TradingPair::class,
            'parsed_news_trading_pair',
            'parsed_news_id',
            'trading_pair_id',
        );
    }

    /**
     * @return ParsedNews[]|Collection
     */
    public static function getBinanceTradingPairsNews(): Collection
    {
        $q = self::query();
        $q = $q->orderByDesc('published_date');

        $q->where('site_about', 'like', '%binance.com%')
          ->where(function (Builder $q) {
              $q->where('title', 'like', '%Adds%');
              $q->orWhere('title', 'like', '%adds%');
          })
          ->where(function (Builder $q) {
              $q->where('title', 'like', '%Trading%');
              $q->orWhere('title', 'like', '%trading%');
          });

        return $q->get();
    }

    public function getTradingPairsFromTitle(): array
    {
        preg_match_all(pattern: '|[a-zA-Z]{1,30}?/[A-Z]{1,30}|', subject: $this->title, matches: $matches);
        $matches = array_shift($matches);

        return $matches;
    }
}
