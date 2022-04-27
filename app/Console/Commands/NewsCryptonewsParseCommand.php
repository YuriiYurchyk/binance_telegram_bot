<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewsHandlers\Cryptonews\CryptonewsNewsHandler;
use Artisan;

class NewsCryptonewsParseCommand extends Command
{
    protected $signature = 'parser-news:cryptonews';

    protected $description = 'Command description';

    public function handle()
    {
        $lastPage = 1;

        $parser = app(CryptonewsNewsHandler::class, ['lastPage' => $lastPage]);
        $parser->handle();
        Artisan::call('telegram-bot:notify');


        return 0;
    }
}
