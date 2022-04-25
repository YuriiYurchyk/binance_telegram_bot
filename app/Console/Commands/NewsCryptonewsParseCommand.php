<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewsHandlers\CryptonewsNewsHandler;

class NewsCryptonewsParseCommand extends Command
{
    protected $signature = 'parser-news:cryptonews';

    protected $description = 'Command description';

    public function handle()
    {
        $parser = app(CryptonewsNewsHandler::class);
        $parser->handle();

        return 0;
    }
}