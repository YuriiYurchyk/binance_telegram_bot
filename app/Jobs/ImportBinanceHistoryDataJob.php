<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ImporterBinanceHistoryDataFromCsv;

class ImportBinanceHistoryDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ImporterBinanceHistoryDataFromCsv $importer;

    public function __construct(
        private int $handledFileId
    ) {
        $this->importer = new ImporterBinanceHistoryDataFromCsv();
    }

    public function handle()
    {
        $this->importer->handle($this->handledFileId);
    }
}
