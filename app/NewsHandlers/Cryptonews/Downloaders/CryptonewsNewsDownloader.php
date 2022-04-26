<?php declare(strict_types=1);

namespace App\NewsHandlers\Cryptonews\Downloaders;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client;
use App\NewsHandlers\Interfaces\Downloaders\ArticlesDownloaderInterface;

class CryptonewsNewsDownloader implements ArticlesDownloaderInterface
{
    public function __construct(private Client $httpClient)
    {
        //
    }

    public function getArticlesList(UriInterface|Uri $uri): string
    {
        $html = $this->httpClient->get($uri)->getBody()->getContents();

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