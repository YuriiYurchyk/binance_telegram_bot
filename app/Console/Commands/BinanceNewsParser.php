<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Arr;
use App\Models\BinanceNews;
use Telegram\Bot\Api;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BinanceNewsParser extends Command
{
    protected $signature = 'binance:news';

    protected $description = 'Command description';

    public function __construct(private Api $telegram)
    {
        parent::__construct();
    }

    public function handle()
    {
        //        BinanceNews::truncate();

        $this->loadFromBinance();
        BinanceNews::scopeIsNew(BinanceNews::query())->exists();
//        if (BinanceNews::scopeIsNew(BinanceNews::query())->exists()) {
//            $this->sendUpdatesToTg();
//        }

        return 0;
    }

    private function loadFromBinance()
    {
        $url = 'https://www.binance.com/bapi/composite/v1/public/cms/article/list/query?type=1&pageNo=1&pageSize=20';
        $response = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'lang' => 'en',
            'pragma' => 'no-cache',
        ])->get($url);

        $newsData = json_decode($response->body(), true);
        $catalogs = Arr::get($newsData, 'data.catalogs');
        $catalogs = collect($catalogs);
        $newCryptoNewsCcatalog = $catalogs->where('catalogId', 48)->first();
        $articles = $newCryptoNewsCcatalog['articles'];

        foreach ($articles as $article) {
            if (BinanceNews::where('code', $article['code'])->exists()) {
                continue;
            }

            BinanceNews::create([
                'code' => $article['code'],
                'title' => $article['title'],
                'release_date' => Carbon::createFromTimestampMs((string)$article['releaseDate']),
                'is_new' => true,
            ]);
        }
    }

    private function sendUpdatesToTg()
    {
        /** @var BinanceNews[]|Collection $newBinanceNews */
        $newBinanceNews = BinanceNews::scopeIsNew(BinanceNews::query())
                                     ->orderBy('release_date')->get();

        $messages = $this->prepareBinanceNewsForSend($newBinanceNews);
        foreach ($messages as $messageBlock) {
            $message = implode(PHP_EOL . PHP_EOL, $messageBlock);
            $this->sendTgMessages($message);
        }
        $this->markBinanceNewsAsOld($newBinanceNews);

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

    /**
     * @param  Collection|BinanceNews[]  $newNews
     *
     * @return array
     */
    private function markBinanceNewsAsOld(Collection $newNews)
    {
        $newNews->each(function (BinanceNews $binanceNews) {
            $binanceNews->is_new = 0;
            $binanceNews->save();
        });
    }

    /**
     * @param  Collection|BinanceNews[]  $newNews
     *
     * @return array
     */
    private function prepareBinanceNewsForSend(Collection $newNews)
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
