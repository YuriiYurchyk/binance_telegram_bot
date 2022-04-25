<?php declare(strict_types=1);

namespace App\NewsHandlers;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use App\Services\UrlPaginator;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\ParsedNews;
use Log;
use App\NewsHandlers\Parsers\CryptonewsNewsListParser;
use App\NewsHandlers\Parsers\CryptonewsNewsListNewsItemParser;

class CryptonewsNewsHandler
{
    private Uri|UriInterface $basePsrUri;

    private CryptonewsNewsListParser $newsListParser;

    private CryptonewsNewsListNewsItemParser $newsNodeParser;

    private UrlPaginator $urlPaginator;

    public function __construct(private Client $httpClient)
    {
        $url = "https://cryptonews.net/";
        $newsUrl = "https://cryptonews.net/news/market/";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $lastPage = 1;
        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
        );

        $this->newsListParser = new CryptonewsNewsListParser();
        $this->newsNodeParser = new CryptonewsNewsListNewsItemParser(clone $this->basePsrUri);
    }

    public function handle()
    {
        Log::info('Start handle Cryptonews.net');
        $this->handlePages();
        Log::info('End handle Cryptonews.net');

        return 0;
    }

    private function handlePages(): void
    {
        while (true) {
            $this->handlePage();
            Log::info("Parse Cryptonews.net page: {$this->urlPaginator->getCurrentPage()}/{$this->urlPaginator->getLastPage()}");

            if ($this->urlPaginator->isLast()) {
                break;
            }

            if (1 < $this->urlPaginator->getLastPage()) {
                sleep(2);
            }
            $this->urlPaginator->incrementPage();
        }
    }

    private function handlePage(): void
    {
        $html = $this->getHtml($this->urlPaginator);

        $crawler = new Crawler($html);
        $this->newsListParser->setCrawler($crawler);

        $newsNodes = $this->newsListParser->getNewsNodes();
        $this->handleNewsNodes($newsNodes);

        // set last page number
        if (null === $this->urlPaginator->getLastPage()) {
            $lastPage = $this->newsListParser->getLastPage();
            $this->urlPaginator->setLastPage($lastPage);
        }
    }

    private function handleNewsNodes(Crawler $newsNodes): void
    {
        foreach ($newsNodes as $newsNode) {
            $this->newsNodeParser->setCrawler($newsNode);
            $newsUrl = $this->newsNodeParser->getNewsUrl();

            $parsedNews = ParsedNews::where('url', $newsUrl)->first();
            if ($parsedNews
                && empty($parsedNews->published_date)
                && !empty($this->newsNodeParser->getPublishedDate())
            ) {
                $parsedNews->published_date = $this->newsNodeParser->getPublishedDate();
                $parsedNews->save();
            }

            if (!empty($parsedNews)) {
                continue;
            }

            $publishedDate = $this->newsNodeParser->getPublishedDate()
                                                  ?->setTimezone(config('app.timezone'));

            /** @var ParsedNews $parsedNews */
            $parsedNews = ParsedNews::make();
            $parsedNews->title = $this->newsNodeParser->getTitle();
            $parsedNews->url = $newsUrl;
            $parsedNews->site_about = $this->newsNodeParser->getSiteAboutCurrentNewsUrl();
            $parsedNews->site_source = $this->basePsrUri->getHost();
            $parsedNews->published_date = $publishedDate;
            $parsedNews->is_new = 1;
            $parsedNews->save();
        }
    }

    private function getHtml(UrlPaginator $urlPaginator)
    {
        $html = $this->httpClient->get($urlPaginator->getCurrentUrl())->getBody()->getContents();

        return $html;

        $cacheFilePath = base_path("pages_cache/{$urlPaginator->getCurrentPage()}.html");
        if (file_exists($cacheFilePath)) {
            $html = file_get_contents($cacheFilePath);
        } else {
            $html = $this->httpClient->get($urlPaginator->getCurrentUrl())->getBody()->getContents();
            file_put_contents($cacheFilePath, $html);
        }

        return $html;
    }
}