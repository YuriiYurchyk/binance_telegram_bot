<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Arr;
use Log;
use Artisan;
use App\Models\ParsedNews;
use GuzzleHttp\Psr7\Uri;
use App\Parsers\BinanceNewsItemParser;
use GuzzleHttp\Client;
use Psr\Http\Message\UriInterface;
use App\Services\UrlPaginator;

class BinanceNewsParserCommand extends Command
{
    protected $signature = 'parser-news:binance';

    protected $description = 'Command description';

    private Uri|UriInterface $basePsrUri;

    private Uri|UriInterface $newsPsrUrl;
    private UrlPaginator     $urlPaginator;

    public function __construct(private Client $httpClient)
    {
        parent::__construct();

        $url = "https://www.binance.com/";
        $newsUrl =
            "https://www.binance.com/bapi/composite/v1/public/cms/article/list/query?type=1&pageNo=1&pageSize=20";

        $this->newsPsrUrl = new Uri($newsUrl);
        $this->basePsrUri = new Uri($url);

        $lastPage = 1;
        $this->urlPaginator = new UrlPaginator(basePsrUri: $this->newsPsrUrl, lastPage: $lastPage, pageQueryParam: 'pageNo');
    }

    public function handle()
    {
        // BinanceNews::truncate();

        $this->begin();
        //        Artisan::call(command: 'telegram:notify');

        //        sleep(30);

        return 0;
    }

    private function begin()
    {
        Log::info('Start handle Binance Crypto News');
        $this->loadFromBinance();
        Log::info('End handle Binance Crypto News');
    }

    private function loadFromBinance()
    {
        $response = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'lang' => 'en',
            'pragma' => 'no-cache',
        ])->get((string) $this->urlPaginator->getCurrentUrl());

        $newsData = json_decode($response->body(), true);
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
