<?php declare(strict_types=1);

namespace App\NewsHandlers\Cryptonews;

use GuzzleHttp\Psr7\Uri;
use App\Services\UrlPaginator;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\ParsedNews;
use App\NewsHandlers\Cryptonews\Parsers\CryptonewsNewsListParser;
use App\NewsHandlers\Cryptonews\Parsers\CryptonewsNewsPreviewParser;
use App\NewsHandlers\Cryptonews\Downloaders\CryptonewsNewsDownloader;
use App\NewsHandlers\BaseNewsHandler;
use DOMElement;

class CryptonewsNewsHandler extends BaseNewsHandler
{
    private CryptonewsNewsPreviewParser $cryptonewsNewsPreviewParser;

    public function __construct(
        private CryptonewsNewsDownloader $cryptonewsNewsDownloader,
        private CryptonewsNewsListParser $cryptonewsNewsListParser,
    ) {
        $url = "https://cryptonews.net/";
        $newsUrl = "https://cryptonews.net/news/market/";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $lastPage = 1;
        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
        );

        $this->cryptonewsNewsPreviewParser = new CryptonewsNewsPreviewParser(clone $this->basePsrUri);
    }

    protected function handlePage(): void
    {
        $html = $this->cryptonewsNewsDownloader->getArticlesList($this->urlPaginator->getCurrentUrl());

        $crawler = new Crawler($html);
        $this->cryptonewsNewsListParser->setNewsSourceData($crawler);

        $newsNodes = $this->cryptonewsNewsListParser->getNews();
        $this->handleNewsNodes($newsNodes);

        // set last page number
        if (null === $this->urlPaginator->getLastPage()) {
            $lastPage = $this->cryptonewsNewsListParser->getLastPage();
            $this->urlPaginator->setLastPage($lastPage);
        }
    }

    /**
     * @param  Crawler|DOMElement[]  $newsNodes
     *
     * @return void
     */
    private function handleNewsNodes(Crawler $newsNodes): void
    {
        foreach ($newsNodes as $newsNode) {
            $this->cryptonewsNewsPreviewParser->setSource($newsNode);
            $newsUrl = $this->cryptonewsNewsPreviewParser->getNewsUrl();

            $parsedNews = ParsedNews::where('url', $newsUrl)->first();
            if ($parsedNews
                && empty($parsedNews->published_date)
                && !empty($this->cryptonewsNewsPreviewParser->getPublishedDate())
            ) {
                $parsedNews->published_date = $this->cryptonewsNewsPreviewParser->getPublishedDate();
                $parsedNews->save();
            }

            if (!empty($parsedNews)) {
                continue;
            }

            /** @var ParsedNews $parsedNews */
            $parsedNews = ParsedNews::make();
            $parsedNews->title = $this->cryptonewsNewsPreviewParser->getTitle();
            $parsedNews->url = $newsUrl;
            $parsedNews->site_about = $this->cryptonewsNewsPreviewParser->getSiteAboutCurrentNewsUrl();
            $parsedNews->site_source = $this->basePsrUri->getHost();
            $parsedNews->published_date = $this->cryptonewsNewsPreviewParser->getPublishedDate();
            $parsedNews->is_new = 1;
            $parsedNews->save();
        }
    }
}