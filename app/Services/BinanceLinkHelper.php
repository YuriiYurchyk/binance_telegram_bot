<?php

namespace App\Services;

use App\Models\TradingPair;
use App\Enum\BinancePeriodVO;

class BinanceLinkHelper
{
    private TradingPair     $tradingPair;
    private BinancePeriodVO $period;

    private string $basePath = '/var/www/ssd'; // '/var/www'

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setTradingPair(TradingPair $tradingPair): static
    {
        $this->tradingPair = $tradingPair;

        return $this;
    }

    public function setPeriod(BinancePeriodVO $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getXmlFilesListLink(): string
    {
        $host = "https://s3-ap-northeast-1.amazonaws.com";
        $pairCode = $this->tradingPair->getTradingSpotPairCode();

        $xmlLink = "$host/data.binance.vision?delimiter=/&prefix=data/spot/$this->period/klines/$pairCode/1m/";

        return $xmlLink;
    }

    public function getFileLinkFromUri(string $uri): string
    {
        $dataLink = 'https://data.binance.vision';

        return $dataLink . '/' . trim($uri, '/');
    }

    public function getDestinationPath(): string
    {
        $pairName = $this->tradingPair->getTradingSpotPairCode();

        return rtrim($this->basePath, '/') . '/' . "binance-data/monthly/$pairName";
    }




}