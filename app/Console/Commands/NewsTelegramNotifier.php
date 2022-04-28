<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Illuminate\Database\Eloquent\Collection;
use App\Models\ParsedNews;
use Illuminate\Database\Eloquent\Builder;
use GuzzleHttp\Client;

class NewsTelegramNotifier extends Command
{
    protected $signature = 'telegram-bot:notify';

    protected $description = 'Command description';

    public function __construct(private Api $telegram)
    {
        parent::__construct();
    }

    /**
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle()
    {
        $this->notifyAboutNewNews();

        return 0;
    }

    private function notifyAboutNewNews()
    {
        /** @var ParsedNews[]|Collection $newParsedNews */

        $q = ParsedNews::query();
        $q = ParsedNews::scopeIsNew($q)
                       ->orderBy('published_date');

        $q->where('site_about', 'like', '%binance.com%')
          ->where(function (Builder $q) {
              $q->where('title', 'like', '%Adds%');
              $q->orWhere('title', 'like', '%adds%');
          })
          ->where(function (Builder $q) {
              $q->where('title', 'like', '%Trading%');
              $q->orWhere('title', 'like', '%trading%');
          });

        $newParsedNews = $q->get();
        if ($newParsedNews->isEmpty()) {
            return;
        }

        $needNotify = 15 > $newParsedNews->count();

        $messages = $this->prepareParsedNewsForSend($newParsedNews);


        $needCall = false;
        if ($needNotify) {
            foreach ($messages as $messageBlock) {
                $msg = implode(PHP_EOL . PHP_EOL, $messageBlock);
                $this->sendTgMessages($msg);

                $needCall = $needCall ?: false !== mb_stripos($msg, 'usdt');
                $needCall = $needCall ?: false !== mb_stripos($msg, 'busd');
            }
        }

        $this->markParsedNewsAsOld($newParsedNews);

        if ($needCall) {
            $client = new Client();
            $client->get('http://api.callmebot.com/start.php?user=@YuriiYurchyk&text=This+is+a+robot&lang=en-GB-Standard-B&rpt=2');
        }


        if ($needNotify) {
            // щоб привернути увагу
            foreach (range(5, 0) as $item) {
                $message = $this->telegram->sendMessage([
                    'chat_id' => 304532953,
                    'text' => "NEW NEWS, Yurii",
                ]);

                $this->telegram->deleteMessage([
                    'chat_id' => '304532953',
                    'message_id' => $message->messageId,
                ]);
                sleep(2);
            }
        }
    }


    /**
     * @param  Collection|ParsedNews[]  $newNews
     *
     * @return array
     */
    private function markParsedNewsAsOld(Collection $newNews)
    {
        $newNews->each(function (ParsedNews $parsedNews) {
            $parsedNews->update(['is_new' => false]);
        });
    }

    /**
     * @param  Collection|ParsedNews[]  $newNews
     *
     * @return array
     */
    private function prepareParsedNewsForSend(Collection $newNews)
    {
        $newsForTg = [];
        foreach ($newNews as $news) {
            $newsForTg[] = $news->getForTelegram();
        }

        $newsForTg = array_chunk($newsForTg, 10);

        return $newsForTg;
    }

    private function sendTgMessages(string $message): void
    {
        $response = $this->telegram->sendMessage([
            'chat_id' => 304532953,
            'text' => $message,
            'parse_mode' => "html",
            'disable_web_page_preview' => "1",
        ]);
    }
}
