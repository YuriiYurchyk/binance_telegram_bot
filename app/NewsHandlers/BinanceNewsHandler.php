<?php declare(strict_types=1);

namespace App\NewsHandlers;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use App\Services\UrlPaginator;
use GuzzleHttp\Client;
use Arr;
use Log;
use App\Models\ParsedNews;
use App\NewsHandlers\Parsers\BinanceNewsItemParser;

class BinanceNewsHandler
{
    private Uri|UriInterface $basePsrUri;

    private UrlPaginator $urlPaginator;

    public function __construct(private Client $httpClient)
    {
        $url = "https://www.binance.com/";
        $newsUrl =
            "https://www.binance.com/bapi/composite/v1/public/cms/article/list/query?type=1&pageNo=1&pageSize=20";

        $newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $lastPage = 1;
        $this->urlPaginator = new UrlPaginator(
            basePsrUri: $newsPsrUrl,
            lastPage: $lastPage,
            pageQueryParam: 'pageNo'
        );
    }

    public function handle()
    {
        $this->begin();

        return 0;
    }

    private function begin()
    {
        Log::info('Start handle Binance Crypto News');
        $this->loadNews();
        Log::info('End handle Binance Crypto News');

        return 0;
    }

    private function loadNews(): void
    {
        $response = $this->httpClient->get($this->urlPaginator->getCurrentUrl(), [
            'headers' => [
                'accept' => '*/*',
                'accept-language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
                'cache-control' => 'no-cache',
                'content-type' => 'application/json',
                'lang' => 'en',
                'pragma' => 'no-cache',
            ],
        ]);

        $newsData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $catalogs = Arr::get($newsData, 'data.catalogs');

        $catalogs = collect($catalogs);
        $newCryptoNewsCcatalog = $catalogs->where('catalogId', 48)->first();
        $articles = $newCryptoNewsCcatalog['articles'];

        foreach ($articles as $article) {
            $binanceNewsParser = new BinanceNewsItemParser($article, $this->basePsrUri);

            $newsLink = $binanceNewsParser->getNewsUrl();
            if (ParsedNews::where('url', $newsLink)->exists()) {
                continue;
            }

            /** @var ParsedNews $parsedNews */
            $parsedNews = ParsedNews::make();
            $parsedNews->title = $binanceNewsParser->getTitle();
            $parsedNews->url = $binanceNewsParser->getNewsUrl();
            $parsedNews->site_about = $binanceNewsParser->getSiteAboutCurrentNewsUrl();
            $parsedNews->site_source = $this->basePsrUri->getHost();
            $parsedNews->published_date = $binanceNewsParser->getPublishedDate();
            $parsedNews->is_new = 1;
            $parsedNews->save();
        }
    }
}