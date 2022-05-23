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

    private bool $isFtp = false;

    public function __construct(
        private string $url,
        private string $targetDir,
        private string $targetPathWithFileNameZip,
    ) {
    }

    public function handle()
    {
        if ($this->isFtp) {
            $ftp = $this->getFtp();

            $this->targetPathWithFileNameZip = '/sda1/' . $this->targetPathWithFileNameZip;
            $this->targetDir = '/sda1/' . $this->targetDir;

            if (ftp_nlist($ftp, $this->targetDir) === false) {
                ftp_mkdir($ftp, $this->targetDir);
            }

            try {
                $content = file_get_contents($this->url);
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), '404')) {
                    Cache::forever($this->url, 404);
                } else {
                    throw new Exception('Not found ' . $this->url);
                }
            }

            $tempPath = storage_path('app');
            $path = explode('/', $this->targetPathWithFileNameZip);
            $fileNameZip = array_pop($path);

            $tempPathWithFilenameZip = $tempPath . '/' . $fileNameZip;
            file_put_contents($tempPathWithFilenameZip, $content);
            $zip = new ZipArchive();
            $zip->open($tempPathWithFilenameZip);
            $zip->extractTo($tempPath);
            unlink($tempPathWithFilenameZip);

            $fileNameCsv = str_replace('.zip', '.csv', $fileNameZip);

            $tempPathCsv = $tempPath . '/' . $fileNameCsv;
            $remoteDirCsv = $this->targetDir . '/' . $fileNameCsv;

            ftp_put($ftp, $remoteDirCsv, $tempPathCsv);
            unlink($tempPathCsv);

            return;
        }

        $targetPathWithFileNameZip = '/var/www/' . $this->targetPathWithFileNameZip;
        $targetDir = '/var/www/' . $this->targetDir;

        $targetPathWithFileNameZipSSD = '/var/www/ssd/' . $this->targetPathWithFileNameZip;
        $targetDirSSD = '/var/www/ssd/' . $this->targetDir;

        $targetPathWithFileNameZip = $targetPathWithFileNameZipSSD;
        $targetDir = $targetDirSSD;

        if (!is_dir($targetDir)) {
            if (!mkdir($concurrentDirectory = $targetDir)
                && !is_dir($concurrentDirectory)
            ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created',
                    $concurrentDirectory));
            }
        }

        try {
            $content = file_get_contents($this->url);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                Cache::forever($this->url, 404);
            } else {
                throw new Exception('Not found ' . $this->url);
            }

            return;
        }

        $tempPath = storage_path('app');
        $path = explode('/', $this->targetPathWithFileNameZip);
        $fileNameZip = array_pop($path);

        $tempPathWithFilenameZip = $tempPath . '/' . $fileNameZip;
        file_put_contents($tempPathWithFilenameZip, $content);
        $zip = new ZipArchive();
        $zip->open($tempPathWithFilenameZip);
        $zip->extractTo($tempPath);
        unlink($tempPathWithFilenameZip);

        $fileNameCsv = str_replace('.zip', '.csv', $fileNameZip);

        $tempPathCsv = $tempPath . '/' . $fileNameCsv;
        $remoteDirCsv = $targetDir . '/' . $fileNameCsv;

        $content = file_get_contents($tempPathCsv);
        file_put_contents($remoteDirCsv, $content);

        unlink($tempPathCsv);
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
}
