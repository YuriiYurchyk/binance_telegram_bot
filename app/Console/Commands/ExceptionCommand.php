<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;
use Monolog\Handler\NullHandler;
use App\Enum\BinanceDailyVO;
use App\Enum\BinanceMonthlyVO;
use App\Models\HandledFiles;

class ExceptionCommand extends Command
{
    protected $signature = 'throw:exception';

    protected $description = 'Command description';

    public function handle()
    {

        $imgP = base_path('img.png');

        $url = 'https://data.binance.vision/?prefix=data/spot/monthly/klines/';
        $b = new Browsershot();
        $b->windowSize(1920, 1080)
          ->noSandbox()
          ->waitForFunction(false)
          ->setUrl($url)
        ;

        $b->setOption('functionPolling', Browsershot::POLLING_REQUEST_ANIMATION_FRAME);
        $b->setOption('functionTimeout', 20);

        $b->save($imgP);


        $html = $b->bodyHtml(); // returns the html of the body
        dd($html);


        dd(file_get_contents('https://www.google.com/alerts/feeds/02384417501511553911/13709992515300506746'));
        throw new \Exception();
    }
}
