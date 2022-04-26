<?php declare(strict_types=1);

namespace App\NewsHandlers\Binance;

use GuzzleHttp\Psr7\Uri;
use App\Services\UrlPaginator;
use App\Models\ParsedNews;
use App\NewsHandlers\Binance\Parsers\BinanceNewsPreviewParser;
use App\NewsHandlers\Binance\Downloaders\BinanceNewsDownloader;
use App\NewsHandlers\Binance\Parsers\BinanceNewsListParser;
use App\NewsHandlers\BaseNewsHandler;

class BinanceNewsHandler extends BaseNewsHandler
{
    private BinanceNewsPreviewParser $binanceNewsPreviewParser;

    public function __construct(
        private BinanceNewsDownloader $binanceNewsDownloader,
        private BinanceNewsListParser $binanceNewsListParser,
    ) {
        $url = "https://www.binance.com/";
        $newsUrl =
            "https://www.binance.com/bapi/composite/v1/public/cms/article/list/query?type=1&pageNo=1&pageSize=20";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $lastPage = 1;
        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
            pageQueryParam: 'pageNo',
        );

        $this->binanceNewsPreviewParser = new BinanceNewsPreviewParser(clone $this->basePsrUri);
    }

    protected function handlePage(): void
    {
        $newsData = $this->binanceNewsDownloader->getArticlesList($this->urlPaginator->getCurrentUrl());

        $this->binanceNewsListParser->setNewsSourceData($newsData);
        $articles = $this->binanceNewsListParser->getNews();

        foreach ($articles as $article) {
            $this->binanceNewsPreviewParser->setSource($article);

            $newsLink = $this->binanceNewsPreviewParser->getNewsUrl();
            if (ParsedNews::where('url', $newsLink)->exists()) {
                continue;
            }

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

}