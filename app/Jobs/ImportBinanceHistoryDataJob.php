<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\ImporterBinanceHistoryDataFromCsv;

class ImportBinanceHistoryDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ImporterBinanceHistoryDataFromCsv $importer;

    public function __construct(
        private string $csvFileName,
        private string $dataRange,
        private int $tradingPairId,
    ) {
        $this->importer = new ImporterBinanceHistoryDataFromCsv();
    }

    public function handle()
    {
        ini_set("memory_limit", "-1");
        DB::disableQueryLog();

        $this->importer->handle($this->csvFileName, $this->dataRange, $this->tradingPairId);
    }
}
