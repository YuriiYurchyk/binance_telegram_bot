<?php

namespace App\NewsHandlers\Interfaces\Parsers;

use Carbon\Carbon;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;
use DOMElement;

interface ArticlePreviewParserInterface
{
    public function setSource(DOMElement|array $article): void;

    public function getTitle(): string;

    public function getNewsUrl(): UriInterface|Uri|string;

    public function getPublishedDate(): ?Carbon;

    public function getSiteAboutCurrentNewsUrl(): UriInterface|Uri;
}