<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use ZipArchive;
use App\Models\TradingPair;
use Cache;
use App\Jobs\ImportBinanceHistoryDataJob;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    public function handle()
    {
        $startDateI = Carbon::now()->setYear(2017)->setMonth(01);
        $endDate = Carbon::now()->setYear(2022)->setMonth(03)->setDay(01);

        $tradingPairs = TradingPair::whereHas('parsedNews')->get();

        $dataRange = "1m";

        foreach ($tradingPairs as $pair) {
            $startDate = clone $startDateI;
            while ($startDate->lt($endDate)) {
                $hasStartData = $this->handlePair($startDate, $pair, $dataRange);
                if ($hasStartData) {
                    break;
                }

                $startDate->addMonth();
            }
        }

        return 0;
    }


    private function handlePair($startDate, TradingPair $tradingPair, $dataRange)
    {
        $pairName = $tradingPair->getTradingSpotPairCode();

        $period = 'monthly';
        $dateFormatted = $startDate->format('Y-m');
        $csvFileName = base_path("binance-data/$period/$pairName/$pairName-$dataRange-$dateFormatted.csv");
        if (file_exists($csvFileName)) {

            ImportBinanceHistoryDataJob::dispatch($csvFileName, $dataRange, $tradingPair->id);
            return true;
        }

        $fileName = "$pairName-$dataRange-$dateFormatted.zip";
        $targetPath = base_path("binance-data/$period/$pairName/$fileName");
        $url = "https://data.binance.vision/data/spot/$period/klines/$pairName/$dataRange/$fileName";

        if (404 == Cache::get($url)) {
            return false;
        }

        $this->info($dateFormatted . ' ' . $pairName,);

        try {
            $content = file_get_contents($url);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 404)) {
                Cache::put($url, 404);
            }

            return false;
        }

        $dir = base_path("binance-data/$period/$pairName");
        if (!is_dir($dir)) {
            if (!mkdir($concurrentDirectory = $dir)
                && !is_dir($concurrentDirectory)
            ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created',
                    $concurrentDirectory));
            }
        }
        file_put_contents($targetPath, $content);

        $zip = new ZipArchive();
        $zip->open($targetPath);
        $zip->extractTo($dir);

        unlink($targetPath);

        ImportBinanceHistoryDataJob::dispatch($csvFileName, $dataRange, $tradingPair->id);
        return true;
    }

}
