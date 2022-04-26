<?php declare(strict_types=1);

namespace App\NewsHandlers\Binance\Downloaders;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client;
use App\NewsHandlers\Interfaces\Downloaders\ArticlesDownloaderInterface;

class BinanceNewsDownloader implements ArticlesDownloaderInterface
{
    public function __construct(private Client $httpClient)
    {
        //
    }

    public function getArticlesList(UriInterface|Uri $uri): array
    {
        $response = $this->httpClient->get($uri, [
            'headers' => [
                'accept' => '*/*',
                'accept-language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
                'cache-control' => 'no-cache',
                'content-type' => 'application/json',
                'lang' => 'en',
                'pragma' => 'no-cache',
            ],
        ]);
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}