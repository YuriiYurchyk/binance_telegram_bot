<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class UrlPaginator
{
    private int $currentPage;

    public function __construct(
        private UriInterface|Uri $basePsrUri,
        private ?int $lastPage = null,
        private string $pageQueryParam = 'page',
        int $startPage = 1
    ) {
        $this->setCurrentPage($startPage);
    }

    public function getCurrentUrl(): UriInterface|Uri
    {
        $url = Uri::withQueryValues($this->getBasePsrUri(), [
            $this->getPageQueryParam() => $this->getCurrentPage(),
        ]);

        return $url;
    }

    public function incrementPage(): bool
    {
        if ($this->isLast()) {
            return false;
        }

        $this->setCurrentPage($this->getCurrentPage() + 1);

        return true;
    }

    public function isLast(): bool
    {
        return $this->getCurrentPage() === $this->getLastPage();
    }


    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    private function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    public function getLastPage(): ?int
    {
        return $this->lastPage;
    }

    public function setLastPage(int $lastPage): void
    {
        $this->lastPage = $lastPage;
    }

    private function getPageQueryParam(): string
    {
        return $this->pageQueryParam;
    }

    private function getBasePsrUri(): UriInterface|Uri
    {
        return $this->basePsrUri;
    }

}