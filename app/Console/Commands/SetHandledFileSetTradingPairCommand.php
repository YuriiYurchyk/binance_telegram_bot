<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HandledFiles;
use App\Models\TradingPair;

class SetHandledFileSetTradingPairCommand extends Command
{
    protected $signature = 'handled-file:set-pair';

    protected $description = 'Command description';

    public function handle()
    {
       HandledFiles::each(function (HandledFiles $handledFile) {
            [$pairCode] = explode('-', $handledFile->file_name);
            $tradingPair = TradingPair::where('pair_code', $pairCode)->first();
            if (!$tradingPair) {
                return;
            }

            $handledFile->tradingPair()->associate($tradingPair);
            $handledFile->save();

            $this->info("HandledFile id=$handledFile->id");;
        });

        return 0;
    }
}
