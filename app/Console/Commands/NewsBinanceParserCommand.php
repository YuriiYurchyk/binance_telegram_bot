<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewsHandlers\BinanceNewsHandler;

class NewsBinanceParserCommand extends Command
{
    protected $signature = 'parse:news';

    protected $description = 'Command description';

    public function handle()
    {
        $parser = app(BinanceNewsHandler::class);
        $parser->handle();

        return 0;
    }
}
