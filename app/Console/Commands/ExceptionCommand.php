<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradePair;
use Binance\API;
use Artisan;

class ExceptionCommand extends Command
{
    protected $signature = 'throw:exception';

    protected $description = 'Command description';

    public function handle()
    {
        throw new \Exception();
    }
}
