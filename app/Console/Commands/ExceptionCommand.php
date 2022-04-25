<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExceptionCommand extends Command
{
    protected $signature = 'throw:exception';

    protected $description = 'Command description';

    public function handle()
    {
        throw new \Exception();
    }
}
