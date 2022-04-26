<?php declare(strict_types=1);

namespace App\NewsHandlers\Binance\Parsers;

use App\NewsHandlers\Interfaces\Parsers\ArticleListParserInterface;
use Symfony\Component\DomCrawler\Crawler;
use Arr;

class BinanceNewsListParser implements ArticleListParserInterface
{
    private array $newsApiData;

    public function setNewsSourceData(Crawler|array $newsSourceData): void
    {
        $this->newsApiData = $newsSourceData;
    }

    public function getNews(): array
    {
        $catalogs = Arr::get($this->newsApiData, 'data.catalogs');

        $catalogs = collect($catalogs);
        $newCryptoNewsCcatalog = $catalogs->where('catalogId', 48)->first();
        $articles = $newCryptoNewsCcatalog['articles'];

        return $articles;
    }

    public function getLastPage(): ?int
    {
        return null;
    }
}