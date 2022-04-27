<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewsHandlers\Binance\BinanceNewsHandler;
use Artisan;

class NewsBinanceParserCommand extends Command
{
    protected $signature = 'parser-news:binance';

    protected $description = 'Command description';

    public function handle()
    {
        $lastPage = 1;

        $parser = app(BinanceNewsHandler::class, ['lastPage' => $lastPage]);

        $parser->handle();
        Artisan::call('telegram-bot:notify');

        sleep(30);

        $parser->handle();
        Artisan::call('telegram-bot:notify');

        return 0;
    }
}
