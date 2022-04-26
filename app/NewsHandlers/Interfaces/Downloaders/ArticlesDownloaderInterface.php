<?php

namespace App\NewsHandlers\Interfaces\Downloaders;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;

interface ArticlesDownloaderInterface
{
    public function getArticlesList(UriInterface|Uri $uri): array|string;
}