<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TradingPair;
use Cache;
use App\Jobs\ImportBinanceHistoryDataJob;
use App\Models\BinanceSpotHistory;
use App\Jobs\DownloadBinanceData;
use PharData;
use Phar;
use ZipArchive;
use App\Models\HandledFiles;

class BinanceDownloadSpotDataCommand extends Command
{
    protected $signature = 'binance:download-spot-data';

    protected $description = 'Command description';

    private bool $isFtp = false;


    private TradingPair $currentPair;

    private string $dataRange = "1m";
    //
    //if (!$beDownload) {
    //$startDateMonth = (clone $startDate)->startOfMonth();
    //$endDateMonth = (clone $startDate)->endOfMonth();
    //$dateFormatted = $startDate->format($format);
    //
    //$pairName = $pair->getTradingSpotPairCode();
    //$onlyFileName = "$pairName-$dataRange-$dateFormatted.csv";
    //$csvFileNameWithRelativePath = "binance-data/$period/$pairName/$onlyFileName";
    //
    //
    //$beDownload = $this->downloadPairData($dateFormatted, $dataRange, $period, $pairName, $onlyFileName);
    //}

    public function handle()
    {
        $endDate = Carbon::now()->setYear(2017)->setMonth(05)->setDay(1);
        $startDateI = Carbon::now()->setYear(2022)->setMonth(04)->setDay(1);

        $tradingPairs = TradingPair::orderBy('id')->get();

        $period = 'monthly';

        foreach ($tradingPairs as $pair) {
            $this->currentPair = $pair;

            $startDate = (clone $this->currentPair->binance_added_at);
            $endDate = $this->currentPair->binance_removed_at?->clone() ?: now()->subMonth()->endOfMonth();

            $this->handlePeriod('daily', $startDate, $endDate);
        }


        return 0;
    }

    private function handlePeriod($period, $startDate, $endDate)
    {
        /** @var Carbon $startDate */
        while ($startDate->lt($endDate)) {
            $format = match ($period) {
                'daily' => 'Y-m-d',
                'monthly' => 'Y-m',
            };

            $dateFormatted = $startDate->format($format);

            $pairName = $this->currentPair->getTradingSpotPairCode();
            $onlyFileName = $this->getFilenameWithExt($dateFormatted, '.csv');

//            $fileAlreadyExists = $this->downloadPairData($dateFormatted, $period, $pairName, $onlyFileName);

//                $startDateM = $startDate->clone()->startOfMonth();
//                $endDateM = $startDate->clone()->endOfMonth();
//
//                $this->handlePeriod('daily', $startDateM, $endDateM);

                $csvFileNameWithRelativePath = "binance-data/monthly/$pairName/$onlyFileName";
                $csvFileNamePath1 = '/var/www/' . $csvFileNameWithRelativePath;
                $csvFileNamePathSSD = '/var/www/ssd/' . $csvFileNameWithRelativePath;
                $csvFileNamePath = $csvFileNamePathSSD;

                $onlyFileName = $this->getFilenameWithExt($dateFormatted, '.csv');
//                $this->importInDb($onlyFileName, $csvFileNamePath);
                $this->compressFile($onlyFileName, $csvFileNamePath);




            if ('daily' === $period) {
                $startDate->addDay();
            }
            if ('monthly' === $period) {
                $startDate->addMonth();
            }
        }
    }

    private function getFilenameWithExt($dateFormatted, $ext = '.csv')
    {
        $pairName = $this->currentPair->getTradingSpotPairCode();

        return "{$pairName}-{$this->dataRange}-{$dateFormatted}{$ext}";
    }


    private function downloadPairData(
        string $dateFormatted,
        string $period,
        $pairName,
        $onlyFileName
    ) {
        $csvFileName = "binance-data/monthly/$pairName/$onlyFileName";

        $csvFileNamePath1 = '/var/www/' . $csvFileName;
        $csvFileNamePathSSD = '/var/www/ssd/' . $csvFileName;
        $csvFileNamePath = $csvFileNamePathSSD;

        //        if ($this->isFtp) {
        //            $ftp = $this->getFtp();
        //
        //            if (-1 !== ftp_size($ftp, "/sda1/binance-data/$period/$pairName/$onlyFileName")) {
        //                return true;
        //            }
        //        }

        $gz = $csvFileNamePath . '.gz';
        $zip = str_replace('.csv', '.zip', $csvFileNamePath);

        //        var_dump($csvFileNamePath,file_exists($csvFileNamePath) ,file_exists($gz));
        if (file_exists($csvFileNamePath)
            || file_exists($gz)
            //            || file_exists($zip)
        ) {
            return true;
        }

        $fileName = "$pairName-$this->dataRange-$dateFormatted.zip";
        $targetPathWithFileName = "binance-data/monthly/$pairName/$fileName";
        $url = "https://data.binance.vision/data/spot/$period/klines/$pairName/$this->dataRange/$fileName";

        //        if (404 == (int) Cache::get($url)) {
        //            return false;
        //        }

        $targetDir = "binance-data/monthly/$pairName";

        if ('daily' === $period) {
            DownloadBinanceData::dispatch($url, $targetDir, $targetPathWithFileName);
        }

        return false;
    }


    private function getFtp()
    {
        $hostname = '192.168.0.1';
        $username = 'Yurii';
        $pass = 'CukgQbzuUHpx2vf';

        $ftp = ftp_connect($hostname);

        $status = ftp_login($ftp, $username, $pass);
        ftp_pasv($ftp, true);

        return $ftp;
    }


    private function importInDb($onlyFileName, $csvFileName)
    {
        if (!file_exists($csvFileName)) {
            return false;
        }
        if (HandledFiles::where('file_name', $onlyFileName)->exists()) {
            return false;
        }

        var_dump($csvFileName);
        ImportBinanceHistoryDataJob::dispatch($csvFileName, $this->dataRange, $this->currentPair->id, $onlyFileName);

        return true;
    }

    private $filesDataOnlyFileName    = [];
    private $filesDataCsvFileNamePath = [];

    private function compressFile(string $onlyFileName, string $csvFileNamePath)
    {
        if (!file_exists($csvFileNamePath)) {
            return true;
        }

//        if (!HandledFiles::where('file_name', $onlyFileName)->exists()) {
//            return true;
//        }

        //        $c = file_get_contents($csvFileNamePath);
        //        if ($c === '') {
        //            var_dump($csvFileNamePath);
        //            unlink($csvFileNamePath);
        //        }
        //
        //        return;
        //        if (empty($c) || $c == false) {
        //            dd($csvFileNamePath);
        //        }


        $zipPathWithFilename = str_replace('.csv', '.csv.gz', $csvFileNamePath);
        copy($csvFileNamePath, 'compress.zlib:///' . trim($zipPathWithFilename, '/'));
        unlink($csvFileNamePath);
    }

}
