<?php

namespace App\Parsers;

use Symfony\Component\DomCrawler\Crawler;
use DOMElement;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use Carbon\Carbon;

class CryptonewsNewsListNewsItemParser
{
    private Crawler $crawler;

    public function __construct(private UriInterface|Uri $baseUri)
    {
        //
    }

    public function setCrawler(DOMElement $node)
    {
        $this->crawler = new Crawler($node);
    }

    public function getTitle(): string
    {
        $node = $this->getNewsTitleANode();

        return $node->text();
    }

    public function getNewsUrl(): UriInterface|Uri
    {
        $node = $this->getNewsTitleANode();
        $path = $node->attr('href');

        return $this->baseUri->withPath($path);
    }

    public function getSiteAboutCurrentNewsUrl(): UriInterface|Uri
    {
        $node = $this->crawler->filter('div.desc > div.info span')->first();
        $text = $node->text();

        return new Uri($text);
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

    private function getNewsTitleANode(): Crawler
    {
        return $this->crawler->filter('div > a.title')->first();
    }
}