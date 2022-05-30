<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;
use App\Models\HandledFiles;

class DownloadBinanceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string  $fileNameNoExt;

    private ?string $destinationPath;

    public function __construct(
        private int $handledFileId,
    ) {
    }

    public function handle()
    {
        $handledFile = HandledFiles::findOrFail($this->handledFileId);


        $destinationPath = $handledFile->getDestinationPath();
        $this->createDestinationFolder($destinationPath);

        $url = $handledFile->getBinanceFileUrl();
        $content = file_get_contents($url);

        $tempPath = storage_path('app');
        $tempPathWithFilenameZip = $tempPath . '/' . $handledFile->getFilename('.zip');
        file_put_contents($tempPathWithFilenameZip, $content);
        $zip = new ZipArchive();
        $zip->open($tempPathWithFilenameZip);
        $zip->extractTo($tempPath);
        unlink($tempPathWithFilenameZip);

        //        copy('compress.zip://' . 'file.zip', 'compress.zlib:///' . trim($zipPathWithFilename, '/'));
        //        zip://foo.zip#bar.txt

        $tempPathCsv = $tempPath . '/' . $handledFile->getFilename('.csv');
        $contentFromTemp = file_get_contents($tempPathCsv);
        $csvFullPath =  $handledFile->getFilenameFullPath('.csv');
        file_put_contents($csvFullPath, $contentFromTemp);

        unlink($tempPathCsv);

        ImportBinanceHistoryDataJob::dispatch($handledFile->id)->onQueue('import');
    }

    private function createDestinationFolder(string $destinationPath): void
    {
        if (!is_dir($destinationPath)) {
            if (!mkdir($destinationPath, 0777, true)
                && !is_dir($destinationPath)
            ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created',
                    $destinationPath));
            }
        }
    }

}
