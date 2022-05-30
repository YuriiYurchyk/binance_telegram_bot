<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingPair;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Enum\BinanceMonthlyVO;
use App\Jobs\BinanceParseXmlFileJob;

class BinanceParseXmlFilesCommand extends Command
{
    protected $signature = 'binance:parse-xml-files';

    protected $description = 'Command description';

    protected BinancePeriodVO $period;

    public function handle()
    {
//        $tradingPairs = TradingPair::scopeActive(TradingPair::query())->get();
        $tradingPairs = TradingPair::get();
        $periodMonthly = new BinanceMonthlyVO();
        $periodDaily = new BinanceDailyVO();

        foreach ($tradingPairs as $tradingPair) {
            BinanceParseXmlFileJob::dispatch($tradingPair->id, $periodMonthly)->onQueue('download');
            BinanceParseXmlFileJob::dispatch($tradingPair->id, $periodDaily)->onQueue('download');
        }

        return 0;
    }


}
