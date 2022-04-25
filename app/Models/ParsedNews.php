<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ParsedNews extends Model
{
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

    public static function scopeIsNew($q)
    {
        return $q->where('is_new', 1);
    }

    public function getForTelegram(): string
    {
        $blocks = [
            "<b>$this->release_date</b>",
            $this->title,
            "Source: <a href=\"$this->site_source\">$this->site_source</a> ",
            "About: <a href=\"$this->site_about\">$this->site_about</a> ",
            "<a href=\"$this->url\">Read Full Version</a>",
        ];

        return implode(PHP_EOL, $blocks);
    }

}
