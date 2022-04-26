<?php declare(strict_types=1);

namespace App\NewsHandlers\Binance\Parsers;

use Carbon\Carbon;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use App\NewsHandlers\Interfaces\Parsers\ArticlePreviewParserInterface;
use DOMElement;

class BinanceNewsPreviewParser implements ArticlePreviewParserInterface
{
    private array $article;

    public function __construct(private UriInterface|Uri $baseUri)
    {
        //
    }

    public function setSource(DOMElement|array $article): void
    {
        $this->article = $article;
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