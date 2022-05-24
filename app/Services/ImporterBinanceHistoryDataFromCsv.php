<?php declare(strict_types=1);

namespace App\Services;

use LoadFile;
use App\Models\HandledFiles;

class ImporterBinanceHistoryDataFromCsv
{
    public function handle(string $csvFullPath, int $tradingPairId)
    {
        $onlyFileNameWithExt = explode('/', $csvFullPath);
        $onlyFileNameWithExt = array_pop($onlyFileNameWithExt);

        if (HandledFiles::where('file_name', $onlyFileNameWithExt)->exists()) {
            return false;
        };

        $tempFilePointer = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFilePointer)['uri'];

        $csv = file_get_contents($csvFullPath);

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

        $fp = fopen($tempFilePath, 'wb');
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

        LoadFile::file($tempFilePath, $local = true)
                ->into('binance_spot_history')
                ->columns($header)
                ->fieldsTerminatedBy(",")
                ->fieldsEscapedBy("\\\\")
                ->fieldsEnclosedBy('"')
                ->linesTerminatedBy(PHP_EOL)
                ->ignoreLines(1)
                ->load();

        HandledFiles::insert([
            'file_name' => $onlyFileNameWithExt,
        ],
        );

        fclose($tempFilePointer);

        $this->compressFile($csvFullPath);
    }

    private function compressFile(string $csvFullPath): void
    {
        $csvGzFullPath = $csvFullPath . '.gz';

        copy($csvFullPath, 'compress.zlib:///' . trim($csvGzFullPath, '/'));
        unlink($csvFullPath);
    }
}
