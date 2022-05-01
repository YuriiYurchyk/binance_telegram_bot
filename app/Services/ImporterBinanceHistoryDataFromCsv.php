<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ImporterBinanceHistoryDataFromCsv
{
    public function handle($csvFileName, string $dataRange, int $tradingPairId, ?string $fileNameWithoutPath = null)
    {
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
}
