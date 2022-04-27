<?php declare(strict_types=1);

namespace App\NewsHandlers\Cryptonews;

use GuzzleHttp\Psr7\Uri;
use App\Services\UrlPaginator;
use App\Models\ParsedNews;
use App\NewsHandlers\BaseNewsHandler;
use App\NewsHandlers\Cryptonews\Downloaders\CryptonewsNewsDownloader;
use App\NewsHandlers\Cryptonews\Parsers\CryptonewsNewsListParser;
use App\NewsHandlers\Cryptonews\Parsers\CryptonewsNewsPreviewParser;
use Symfony\Component\DomCrawler\Crawler;
use DOMElement;
use Carbon\Carbon;

class CryptonewsNewsHandler extends BaseNewsHandler
{
    private CryptonewsNewsPreviewParser $cryptonewsNewsPreviewParser;

    public function __construct(
        protected int $lastPage,
        protected CryptonewsNewsDownloader $cryptonewsNewsDownloader,
        protected CryptonewsNewsListParser $cryptonewsNewsListParser,
    ) {
        $url = "https://cryptonews.net/";
        $newsUrl = "https://cryptonews.net/news/market/";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
        );

        $this->cryptonewsNewsPreviewParser = new CryptonewsNewsPreviewParser(clone $this->basePsrUri);
    }

    protected function handleNewsListPage(): void
    {
        $html = $this->cryptonewsNewsDownloader->getArticlesList($this->urlPaginator->getCurrentUrl());

        $crawler = new Crawler($html);
        $this->cryptonewsNewsListParser->setNewsSourceData($crawler);

        $newsNodes = $this->cryptonewsNewsListParser->getNews();
        foreach ($newsNodes as $newsNode) {
            $this->handleNewsListItem($newsNode);
        }

        // set last page number
        if (null === $this->urlPaginator->getLastPage()) {
            $lastPage = $this->cryptonewsNewsListParser->getLastPage();
            $this->urlPaginator->setLastPage($lastPage);
        }
    }

    protected function handleNewsListItem(DOMElement $newsNode): void
    {
        $this->cryptonewsNewsPreviewParser->setSource($newsNode);

        $newsUrl = $this->cryptonewsNewsPreviewParser->getNewsUrl();
        $parsedNews = ParsedNews::where('url', $newsUrl)->first();
        if ($parsedNews) {
            $this->addNewsPublishedDateIfEmpty($parsedNews);

            return;
        }

        $this->createNewsItem();
    }

    protected function createNewsItem(): void
    {
        /** @var ParsedNews $parsedNews */
        $parsedNews = ParsedNews::make();
        $parsedNews->title = $this->cryptonewsNewsPreviewParser->getTitle();
        $parsedNews->url = $this->cryptonewsNewsPreviewParser->getNewsUrl();
        $parsedNews->site_about = $this->cryptonewsNewsPreviewParser->getSiteAboutCurrentNewsUrl();
        $parsedNews->site_source = $this->basePsrUri->getHost();
        $parsedNews->published_date = $this->cryptonewsNewsPreviewParser->getPublishedDate();
        $parsedNews->is_new = 1;
        $parsedNews->save();
    }

    protected function addNewsPublishedDateIfEmpty(ParsedNews $parsedNews): void
    {
        if ($parsedNews->published_date) {
            return;
        }

        $publishedDate = $this->cryptonewsNewsPreviewParser->getPublishedDate();
        if (!($publishedDate instanceof Carbon)) {
            return;
        }

        $parsedNews->published_date = $publishedDate;
        $parsedNews->save();
    }
}