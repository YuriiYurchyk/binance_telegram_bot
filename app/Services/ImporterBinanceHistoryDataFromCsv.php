<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use LoadFile;
use App\Models\HandledFiles;

class ImporterBinanceHistoryDataFromCsv
{
    public function handle(string $csvFileName, string $dataRange, int $tradingPairId, string $fileNameWithoutPath)
    {
        $this->method2($csvFileName, $dataRange, $tradingPairId, $fileNameWithoutPath);

        return;

        $csv = file_get_contents($csvFileName);

        $fields = [
            0 => 'open_time',
            1 => 'open',
            2 => 'high',
            3 => 'low',
            4 => 'close',
            6 => 'close_time',
        ];
        $csvReader = new CsvReader($csv, $fields, "\n");

        $rows = [];
        while ($line = $csvReader->getNextParsedLine()) {
            $rows[] = array_merge([
                'data_range' => $dataRange,
                'trading_pair_id' => $tradingPairId,
                'source_file_name' => $fileNameWithoutPath,
            ], $line);

            if (1000 === count($rows)) {
                DB::table('binance_spot_history')->insertOrIgnore($rows);
                $rows = [];
            }
        }
        if (!empty($rows)) {
            DB::table('binance_spot_history')->insertOrIgnore($rows);
        }
    }

    private function method2(string $csvFileName, string $dataRange, int $tradingPairId, string $fileNameWithoutPath)
    {
        $tempPointer = tmpfile();
        $path = stream_get_meta_data($tempPointer)['uri'];

        $csv = file_get_contents($csvFileName);

        $fields = [
            0 => 'open_time',
            1 => 'open',
            2 => 'high',
            3 => 'low',
            4 => 'close',
            5 => 'close_time',
        ];
        $csvReader = new CsvReader($csv, $fields, "\n");

        $header = array_merge(["trading_pair_id"], $fields);

        $fp = fopen($path, 'wb');
        //Write the header
        fputcsv($fp, $header);

        while ($line = $csvReader->getNextParsedLine()) {
            $row = array_merge([
                'trading_pair_id' => $tradingPairId,
            ], $line);

            //Write fields
            fputcsv($fp, $row);
        }
        fclose($fp);

        LoadFile::file($path, $local = true)
                ->into('binance_spot_history')
                ->columns($header)
                ->fieldsTerminatedBy(",")
                ->fieldsEscapedBy("\\\\")
                ->fieldsEnclosedBy('"')
                ->linesTerminatedBy(PHP_EOL)
                ->ignoreLines(1)
                ->load();

        HandledFiles::insertOrIgnore([
            [
                'file_name' => $fileNameWithoutPath,
            ],
        ]);

        fclose($tempPointer);
    }
}
