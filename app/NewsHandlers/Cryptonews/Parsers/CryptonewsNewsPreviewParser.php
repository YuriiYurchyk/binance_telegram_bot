<?php declare(strict_types=1);

namespace App\NewsHandlers\Cryptonews\Parsers;

use Symfony\Component\DomCrawler\Crawler;
use DOMElement;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use Carbon\Carbon;
use App\NewsHandlers\Interfaces\Parsers\ArticlePreviewParserInterface;

class CryptonewsNewsPreviewParser implements ArticlePreviewParserInterface
{
    private Crawler $crawler;

    public function __construct(private UriInterface|Uri $baseUri)
    {
        //
    }

    public function setSource(DOMElement|array $article): void
    {
        $this->crawler = new Crawler($article);
    }

    public function getTitle(): string
    {
        $node = $this->getNewsTitleANode();

        return $node->text();
    }

    public function getNewsUrl(): UriInterface|Uri|string
    {
        $node = $this->getNewsTitleANode();
        $path = $node->attr('href');

        return $this->baseUri->withPath($path);
    }

    public function getPublishedDate(): ?Carbon
    {
        $node = $this->crawler->filter('div.desc > div.info span.datetime');
        $text = $node->text();

        try {
            $dateTime = Carbon::parse($text);
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            $dateTime = null;
        }

        return $dateTime;
    }

    public function getSiteAboutCurrentNewsUrl(): UriInterface|Uri
    {
        $node = $this->crawler->filter('div.desc > div.info span')->first();
        $text = $node->text();

        return new Uri($text);
    }

    private function getNewsTitleANode(): Crawler
    {
        return $this->crawler->filter('div > a.title')->first();
    }
}