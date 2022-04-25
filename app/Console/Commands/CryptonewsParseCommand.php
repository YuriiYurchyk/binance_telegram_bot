<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Services\UrlPaginator;
use Symfony\Component\DomCrawler\Crawler;
use App\Parsers\CryptonewsNewsListParser;
use App\Parsers\CryptonewsNewsListNewsNodeParser;
use GuzzleHttp\Psr7\Uri;
use App\Models\ParsedNews;

class CryptonewsParseCommand extends Command
{
    protected $signature = 'parser:cryptonews';

    protected $description = 'Command description';


    private Uri $basePsrUri;

    private string $newsPath;

    private CryptonewsNewsListParser $newsListParser;

    private CryptonewsNewsListNewsNodeParser $newsNodeParser;

    public function __construct(private Client $httpClient)
    {
        parent::__construct();

        $url = "https://cryptonews.net";
        $this->newsPath = "/news/market/";

        $this->basePsrUri = new Uri($url);
        $this->newsListParser = new CryptonewsNewsListParser();
        $this->newsNodeParser = new CryptonewsNewsListNewsNodeParser(clone $this->basePsrUri);
    }

    public function handle()
    {
        $lastPage = 1;

        $newsPsrUrl = $this->basePsrUri->withPath($this->newsPath);
        $urlPaginator = new UrlPaginator(basePsrUri: $newsPsrUrl, lastPage: $lastPage);

        while (true) {
            $html = $this->getHtml($urlPaginator);
            $crawler = new Crawler($html);
            $this->newsListParser->setCrawler($crawler);

            $newsNodes = $this->newsListParser->getNewsNodes();
            $this->handleNewsNodes($newsNodes);

            // set last page number
            if (null === $urlPaginator->getLastPage()) {
                $lastPage = $this->newsListParser->getLastPage();
                $urlPaginator->setLastPage($lastPage);
            }

            if ($urlPaginator->isLast()) {
                break;
            }
            $urlPaginator->incrementPage();
        }

        return 0;
    }

    private function handleNewsNodes(Crawler $newsNodes): void
    {
        foreach ($newsNodes as $newsNode) {
            $this->newsNodeParser->setCrawler($newsNode);
            $newsUrl = $this->newsNodeParser->getNewsUrl();

            $parsedNews = ParsedNews::where('url', $newsUrl)->first();
            if (empty($parsedNews)
                || (!empty($this->newsNodeParser->getPublishedDate())
                    && empty($parsedNews->published_date))
            ) {
                continue;
            }

            /** @var ParsedNews $parsedNews */
            $parsedNews = ParsedNews::make();
            $parsedNews->title = $this->newsNodeParser->getTitle();
            $parsedNews->url = $newsUrl;
            $parsedNews->site_about = $this->newsNodeParser->getSiteAboutCurrentNewsUrl();
            $parsedNews->site_source = $this->basePsrUri->getHost();
            $parsedNews->published_date = $this->newsNodeParser->getPublishedDate();
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
