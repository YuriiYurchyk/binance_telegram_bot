<?php

namespace App\Parsers;

use Symfony\Component\DomCrawler\Crawler;
use DOMElement;

class CryptonewsNewsListParser
{
    private Crawler $crawler;

    public function setCrawler(Crawler $crawler): void
    {
        $this->crawler = $crawler;
    }

    /**
     * @return Crawler|DOMElement[]
     */
    public function getNewsNodes(): Crawler
    {
        $newsNodes = $this->crawler->filter('section > div.news-item');

        return $newsNodes;
    }

    public function getLastPage(): int
    {
        $paginationBlock = $this->crawler->filter('#pagination a');
        $lastPageNodePlace = $paginationBlock->count() - 2; // start from 0

        return $paginationBlock->getNode($lastPageNodePlace)->textContent;
    }
}