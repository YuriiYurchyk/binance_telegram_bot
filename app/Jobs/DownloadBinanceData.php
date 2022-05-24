<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Cache;
use ZipArchive;
use Exception;

class DownloadBinanceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $url,
        private string $destinationPath,
        private string $fileNameNoExt,
        private int $tradingPairId,
    ) {
    }

    public function handle()
    {
        $this->createDestinationFolder();

        var_dump($this->url);

        try {
            $content = file_get_contents($this->url);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                Cache::forever($this->url, 404);

                return;
            }

            throw new Exception('Not found ' . $this->url);
        }

        $tempPath = storage_path('app');

        $tempPathWithFilenameZip = $tempPath . '/' . $this->fileNameNoExt . '.zip';
        file_put_contents($tempPathWithFilenameZip, $content);
        $zip = new ZipArchive();
        $zip->open($tempPathWithFilenameZip);
        $zip->extractTo($tempPath);
        unlink($tempPathWithFilenameZip);

        //        copy('compress.zip://' . 'file.zip', 'compress.zlib:///' . trim($zipPathWithFilename, '/'));
        //        zip://foo.zip#bar.txt

        $fileNameCsv = $this->fileNameNoExt . '.csv';
        $csvFullPath = $this->destinationPath . '/' . $fileNameCsv;

        $tempPathCsv = $tempPath . '/' . $fileNameCsv;
        $contentFromTemp = file_get_contents($tempPathCsv);
        file_put_contents($csvFullPath, $contentFromTemp);

        unlink($tempPathCsv);

        ImportBinanceHistoryDataJob::dispatch($csvFullPath, $this->tradingPairId);
    }

    private function createDestinationFolder(): void
    {
        if (!is_dir($this->destinationPath)) {
            if (!mkdir($this->destinationPath, 0777, true)
                && !is_dir($this->destinationPath)
            ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created',
                    $this->destinationPath));
            }
        }
    }

}
