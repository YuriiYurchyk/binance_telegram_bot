<?php declare(strict_types=1);

namespace App\NewsHandlers\Parsers;

use Carbon\Carbon;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;

class BinanceNewsItemParser
{
    public function __construct(private array $article, private UriInterface|Uri $baseUri)
    {
        //
    }

    public function getTitle(): string
    {
        return $this->article['title'];
    }

    public function getNewsUrl(): string
    {
        $newsLink = "https://www.binance.com/en/support/announcement/{$this->article['code']}/";

        return $newsLink;
    }

    public function getPublishedDate(): Carbon
    {
        return Carbon::createFromTimestampMs($this->article['releaseDate'])
                     ->setTimezone(config('app.timezone'));
    }

    public function getSiteAboutCurrentNewsUrl(): UriInterface|Uri
    {
        $hast = $this->baseUri->getHost();

        return new Uri($hast);
    }

}