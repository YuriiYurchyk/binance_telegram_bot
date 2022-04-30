<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingPair;
use App\Models\ParsedNews;
use Exception;

class CreateTradingPairParsedNewsRelationsCommand extends Command
{
    protected $signature = 'trading-pairs:news-relations';

    protected $description = 'Command description';

    public function handle()
    {
        $parsedNews = ParsedNews::getBinanceTradingPairsNews();

        foreach ($parsedNews as $newsItem) {
            $this->handleNews($newsItem);
        }

        return 0;
    }

    private function handleNews(ParsedNews $newsItem): void
    {
        $newsTradingPairs = $newsItem->getTradingPairsFromTitle();

        foreach ($newsTradingPairs as $tradingPairCode) {
            $this->handleTradingPairCode($newsItem, $tradingPairCode);
        }
    }

    private function handleTradingPairCode(ParsedNews $newsItem, string $tradingPairCode): void
    {
        if (in_array($tradingPairCode, ['R/USDT', 'IOST/BNB'])) { // deleted pairs
            return;
        }

        [$baseCoin, $quoteCoin] = explode('/', $tradingPairCode);

        /** @var TradingPair $tradingPair */
        $tradingPair = TradingPair::whereIn('base_coin', [$baseCoin, $quoteCoin])
                                  ->whereIn('quote_coin', [$baseCoin, $quoteCoin])->first();
        if (!$tradingPair) {
            throw new Exception('Unidentified trade pair: ' . $tradingPairCode);
        }

        if ($tradingPair->parsedNews()->wherePivot('parsed_news_id', $newsItem->id)->exists()) {
            return;
        }

        $tradingPair->parsedNews()->save($newsItem);
    }


}
