<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use App\Models\TradePair;
use Illuminate\Database\Eloquent\Collection;

class TelegramBot extends Command
{
    protected $signature = 'bot:run';

    protected $description = 'Command description';

    /**
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle(Api $telegram)
    {
        // $updates = $telegram->getUpdates(['offset' => 654574470]);

        /** @var Collection $newTradePairs */
        $newTradePairs = TradePair::active()->new()->get();

        if ($newTradePairs->isEmpty()) {
            return 0;
        }

        $messages = [];
        $messageBlock = '';
        $newTradePairs->each(function (TradePair $tradePair) use (&$messages, &$messageBlock) {
            $messageBlock .= $tradePair->code . PHP_EOL;

            if (mb_strlen($messageBlock) > 1000) {
                $messages[] = $messageBlock;
                $messageBlock = '';
            }
        });
        $messages[] = $messageBlock;

        // щоб привернути увагу
        foreach (range(5, 0) as $item) {
            $telegram->sendMessage([
                'chat_id' => 304532953,
                'text' => "wait $item...",
            ]);

            sleep(1);
        }

        foreach ($messages as $message) {
            $response = $telegram->sendMessage([
                'chat_id' => 304532953,
                'text' => $message,
            ]);
        }

        $newTradePairs->each(function (TradePair $tradePair) use (&$messages) {
            $tradePair->update(['new' => 0]);
        });

        return 0;
    }
}
