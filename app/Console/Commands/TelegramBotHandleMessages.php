<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram;

class TelegramBotHandleMessages extends Command
{
    protected $signature = 'telegram-bot:handle-messages';

    protected $description = 'Command description';


    public function handle()
    {
        Telegram::commandsHandler(false);

        return 0;
    }
}
