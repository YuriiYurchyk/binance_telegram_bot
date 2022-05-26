<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Binance\API;
use App\Models\Coin;

class ParseBinanceCoinListCommand extends Command
{
    protected $signature = 'binance:update-coin-list';

    protected $description = 'Command description';

    public function handle()
    {
        $api = new API(config_path('php-binance-api.json'));

        $info = $api->exchangeInfo();

        foreach ($info['symbols'] as $details) {
            $status = 'TRADING' === $details['status'] ? 1 : 0;

            /**
             * @var Coin $baseCoin
             * @var Coin $quoteCoin
             */
            $baseCoin = Coin::firstOrCreate(['name' => $details['baseAsset']]);
            $quoteCoin = Coin::firstOrCreate(['name' => $details['quoteAsset']]);

            $relatedQuoteCoin = $baseCoin->quoteCoins()->where('name', $quoteCoin->name)->first();
            if ($relatedQuoteCoin) {
                $this->updateIfDetailsUpdated($relatedQuoteCoin, $status);

                continue;
            }

            $baseCoin->quoteCoins()->save($quoteCoin, [
                'status' => $status,
            ]);
        }

        return 0;
    }

    private function updateIfDetailsUpdated(Coin $relatedQuoteCoin, int $status): void
    {
        /**
         * @var \Illuminate\Database\Eloquent\Relations\Pivot $pivot
         */
        $pivot = $relatedQuoteCoin->pivot;
        $pivot->status = $status;


        if ($pivot->isDirty()) {
            $pivot->save();
        }
    }
}
