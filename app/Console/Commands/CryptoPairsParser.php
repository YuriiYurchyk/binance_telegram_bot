<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradePair;
use Binance\API;
use Artisan;

class CryptoPairsParser extends Command
{
    protected $signature = 'binance:crypto-pairs';

    protected $description = 'Command description';

    public function handle()
    {
        $api = new API(config_path('php-binance-api.json'));

        $info = $api->exchangeInfo();

        foreach ($info['symbols'] as $tradePairCode => $details) {
            $status = 'TRADING' === $details['status'] ? 1 : 0;

            /**
             * @var TradePair $tradePair
             */
            $tradePair = TradePair::where('code', $tradePairCode)->first();
            if (!$tradePair) {
                $tradePair = TradePair::make([
                    'new' => 1,
                ]);
            }

            $tradePair->new = $tradePair->new ?? 0;
            $tradePair->code = $tradePairCode;
            $tradePair->status = $status;
            $tradePair->save();
        }

        if (TradePair::active()->new()->exists()) {
            Artisan::call(command: 'bot:run');
        }

        return 0;
    }
}
