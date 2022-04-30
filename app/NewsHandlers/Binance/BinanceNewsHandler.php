<?php declare(strict_types=1);

namespace App\NewsHandlers\Binance;

use GuzzleHttp\Psr7\Uri;
use App\Services\UrlPaginator;
use App\Models\ParsedNews;
use App\NewsHandlers\BaseNewsHandler;
use App\NewsHandlers\Binance\Downloaders\BinanceNewsDownloader;
use App\NewsHandlers\Binance\Parsers\BinanceNewsListParser;
use App\NewsHandlers\Binance\Parsers\BinanceNewsPreviewParser;

class BinanceNewsHandler extends BaseNewsHandler
{
    private BinanceNewsPreviewParser $binanceNewsPreviewParser;

    public function __construct(
        protected ?int $lastPage,
        protected BinanceNewsDownloader $binanceNewsDownloader,
        protected BinanceNewsListParser $binanceNewsListParser,
    ) {
        $url = "https://www.binance.com/";
        $newsUrl =
            "https://www.binance.com/bapi/composite/v1/public/cms/article/list/query?type=1&pageNo=1&pageSize=20";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
            pageQueryParam: 'pageNo',
        );

        $this->binanceNewsPreviewParser = new BinanceNewsPreviewParser(clone $this->basePsrUri);
    }

    protected function handleNewsListPage(): void
    {
        $newsData = $this->binanceNewsDownloader->getArticlesList($this->urlPaginator->getCurrentUrl());

        $this->binanceNewsListParser->setNewsSourceData($newsData);
        $articles = $this->binanceNewsListParser->getNews();

        foreach ($articles as $article) {
            $this->handleNewsListItem($article);
        }
    }

    protected function handleNewsListItem(array $article): void
    {
        $this->binanceNewsPreviewParser->setSource($article);

        $newsUrl = $this->binanceNewsPreviewParser->getNewsUrl();
        $parsedNewsExists = ParsedNews::where('url', $newsUrl)->exists();
        if ($parsedNewsExists) {
            return;
        }

        $this->createNewsItem();
    }

    protected function createNewsItem(): void
    {
        /** @var ParsedNews $parsedNews */
        $parsedNews = ParsedNews::make();
        $parsedNews->title = $this->binanceNewsPreviewParser->getTitle();
        $parsedNews->url = $this->binanceNewsPreviewParser->getNewsUrl();
        $parsedNews->site_about = $this->binanceNewsPreviewParser->getSiteAboutCurrentNewsUrl();
        $parsedNews->site_source = $this->basePsrUri->getHost();
        $parsedNews->published_date = $this->binanceNewsPreviewParser->getPublishedDate();
        $parsedNews->is_new = 1;
        $parsedNews->save();
    }

}