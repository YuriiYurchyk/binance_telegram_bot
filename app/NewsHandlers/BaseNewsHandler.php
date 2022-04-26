<?php

namespace App\NewsHandlers;

use Log;
use App\Services\UrlPaginator;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

abstract class BaseNewsHandler
{
    protected Uri|UriInterface $basePsrUri;

    protected UrlPaginator $urlPaginator;

    public function handle()
    {
        Log::info('Start handle ' . static::class);

        while (true) {
            $this->handlePage();
            //            Log::info(static::class
            //                . " parse page: {$this->urlPaginator->getCurrentPage()}/{$this->urlPaginator->getLastPage()}");

            if ($this->urlPaginator->isLast()) {
                break;
            }
            if (1 < $this->urlPaginator->getLastPage()) {
                sleep(2);
            }

            $this->urlPaginator->incrementPage();
        }

        return 0;
    }

    abstract protected function handlePage(): void;
}