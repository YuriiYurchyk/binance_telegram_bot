<?php declare(strict_types=1);

namespace App\Services;

use LoadFile;
use App\Models\HandledFiles;

class ImporterBinanceHistoryDataFromCsv
{
    public function handle(int $handledFileId)
    {
        $handledFile = HandledFiles::findOrFail($handledFileId);
        $csvFullPath = $handledFile->getFilenameFullPath('.csv');
        $tradingPairId = $handledFile->tradingPair->id;

        $onlyFileNameWithExt = explode('/', $csvFullPath);
        $onlyFileNameWithExt = array_pop($onlyFileNameWithExt);

        $handledFile = HandledFiles
            ::where('file_name', $onlyFileNameWithExt)
            ->where('file_exists_on_binance', 1)
            ->first();
        if (1 === $handledFile->handled_success) {
            return false;
        }

        $tempFilePointer = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFilePointer)['uri'];

        if (file_exists($csvFullPath)) {
            $csv = file_get_contents($csvFullPath);
//            if ('' === $csv || false === $csv) {
//                unlink($csvFullPath);
//
//                return;
//            }
        } elseif (file_exists($csvFullPath . '.gz')) {
            $csv = file_get_contents($csvFullPath . '.gz');

//            try {
                $csv = zlib_decode($csv);
//            } catch (\Exception) {
//                unlink($csvFullPath . '.gz');
//
//                return;
//            }
        } else {
            throw new \Exception("Files $csvFullPath (and .gz) not found");
        }

        $fields = [
            0 => 'open_time',
            1 => 'open',
            2 => 'high',
            3 => 'low',
            4 => 'close',
            6 => 'close_time',
        ];
        $csvReader = new CsvReader($csv, $fields, "\n");

        $header = array_merge(["trading_pair_id"], $fields, ['handled_file_id']);

        $fp = fopen($tempFilePath, 'wb');
        //Write the header
        fputcsv($fp, $header);

        while ($line = $csvReader->getNextParsedLine()) {
            $row = array_merge([
                'trading_pair_id' => $tradingPairId,
            ], $line,
                ['handled_file_id' => $handledFile->id]);

            //Write fields
            fputcsv($fp, $row);
        }
        fclose($fp);

//        try {
            LoadFile::file($tempFilePath, true)
                    ->into('binance_spot_history')
                    ->columns($header)
                    ->fieldsTerminatedBy(",")
                    ->fieldsEscapedBy("\\\\")
                    ->fieldsEnclosedBy('"')
                    ->linesTerminatedBy(PHP_EOL)
                    ->ignoreLines(1)
                    ->replace()
                    ->load();
//        } catch (\Exception) {
//            if (file_exists($csvFullPath)) {
//                unlink($csvFullPath);
//            }
//
//            if (file_exists($csvFullPath . '.gz')) {
//                unlink($csvFullPath . '.gz');
//            }
//
//            return;
//        }

        fclose($tempFilePointer);

        if (file_exists($csvFullPath)) {
            $this->compressFile($csvFullPath);
        }

        $handledFile->update(['handled_success' => 1]);
    }

    private function compressFile(string $csvFullPath): void
    {
        $csvGzFullPath = $csvFullPath . '.gz';

        var_dump('compress ' . $csvFullPath);
        copy($csvFullPath, 'compress.zlib:///' . trim($csvGzFullPath, '/'));
        unlink($csvFullPath);
    }
}
