<?php

namespace App\NewsHandlers\Interfaces\Parsers;

use Symfony\Component\DomCrawler\Crawler;

interface ArticlesListParserInterface
{
    public function setNewsSourceData(Crawler|array $newsSourceData): void;

    public function getNews(): Crawler|array;

    public function getLastPage(): ?int;
}