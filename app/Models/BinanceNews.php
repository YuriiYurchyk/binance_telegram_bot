<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinanceNews extends Model
{
    use HasFactory;

    public $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'title',
        'release_date',
        'is_new',
    ];

    protected $casts = [
        'release_date' => 'datetime',
    ];

    public static function scopeIsNew($q)
    {
        return $q->where('is_new', 1);
    }

    public function getForTelegram(): string
    {
        $link = "https://www.binance.com/en/support/announcement/{$this->code}";

        return "<b>$this->release_date</b>"  . PHP_EOL . $this->title . PHP_EOL . "<a href=\"$link\">Read Full Version</a>";
    }
}
