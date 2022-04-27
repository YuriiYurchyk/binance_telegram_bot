<?php declare(strict_types=1);

namespace App\NewsHandlers\Cryptonews\Parsers;

use App\NewsHandlers\Interfaces\Parsers\ArticlesListParserInterface;
use Symfony\Component\DomCrawler\Crawler;
use DOMElement;

class CryptonewsNewsListParser implements ArticlesListParserInterface
{
    private Crawler $crawler;

    public function setNewsSourceData(Crawler|array $crawler): void
    {
        $this->crawler = $crawler;
    }

    /**
     * @return Crawler|DOMElement[]
     */
    public function getNews(): Crawler
    {
        $newsNodes = $this->crawler->filter('section > div.news-item');

        return $newsNodes;
    }

    public function getLastPage(): int
    {
        $paginationBlock = $this->crawler->filter('#pagination a');
        $lastPageNodePlace = $paginationBlock->count() - 2; // start from 0

        return (int)$paginationBlock->getNode($lastPageNodePlace)->textContent;
    }
}