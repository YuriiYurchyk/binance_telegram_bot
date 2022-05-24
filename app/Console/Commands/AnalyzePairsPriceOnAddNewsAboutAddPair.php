<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingPair;
use Carbon\Carbon;
use App\Models\PriceOnAddNewsAboutAddPair;
use Exception;
use App\Models\ParsedNews;
use Illuminate\Database\Eloquent\Builder;

class AnalyzePairsPriceOnAddNewsAboutAddPair extends Command
{
    protected $signature = 'binance:analyze-pairs';

    protected $description = 'Command description';

    public function handle()
    {
        // додати 50 гугл нотіфікейшенів
        // їх парсити по xml і відслідковувати ціну коїнів
        // для останнього можна уже підгружати просто з таблиці цін

        // 1. Вибрати 50 коїнів
        // 2. Додати їх в гугл
        // 3. Налаштувати XML в гуглі
        // 4. Скопіювати посилання на XML в базу даних
        // 5. Періодично скачувати і парсити xml
        // 6. Завантажувати ціну токенів по днях, щоб мати дані за поточний місяць

        // налаштувати скачування даних про коїн по днях по запиту

        PriceOnAddNewsAboutAddPair::truncate();

        /** @var TradingPair[] $tradingPair */
        $tradingPair = TradingPair::whereHas('parsedNews')
                                  ->where(function (Builder $q) {
                                      $q->whereIn('base_coin', ['USDT', ]);
                                      $q->orWhereIn('quote_coin', ['USDT', ]);
                                  })
                                  ->with('parsedNews')
                                  ->get();

        foreach ($tradingPair as $p) {
            /** @var Carbon $pubDate */
            $pubDate = $p->parsedNews->first()->published_date;
            if ($pubDate->gt(now()->subMonth()->startOfMonth())) {
                continue;
            }

            $this->analyzeCoin($p);
        }

        return 0;
    }

    private function analyzeCoin(TradingPair $tradingPair)
    {
        /** @var Carbon $newsPubDate */
        $newsPubDate = $tradingPair->parsedNews->first()->published_date;

        $datePoint0 = $newsPubDate->clone()->addMinutes(1);                   // через 3 хвилини після публікації новини
        $datePoint1 = $newsPubDate->clone()->addMinutes(60);                  // через 15 хвилин після публікації новини
        $datePoint2 = $tradingPair->binance_added_at->clone()->addMinutes(1); // через 3 хвилини після додавання коїна
        $datePoint3 = $tradingPair->binance_added_at->clone()->addMinutes(60);// через 15 хвилин після додавання коїна

        $dates = [$datePoint0, $datePoint1, $datePoint2, $datePoint3];

        $researchedPairs = $tradingPair->getAvailablePairsByDate($newsPubDate);
        if ($researchedPairs->isEmpty()) {
            return;
        }
        /** @var TradingPair $pair */
        $researchedPairs = [$researchedPairs[0]];
        foreach ($researchedPairs as $pair) {
            $basePrice = 0;

            $dateData = [];
            foreach ($dates as $key => $date) {
                try {
                    if (in_array($key, [1, 3])) {
                        $price = $pair->getMaxPriceByPeriod($dates[$key - 1], $date);
                    } else {
                        $price = $pair->getPriceByDateTime($date);
                    }
                } catch (Exception) {
                    continue;
                }
                $basePrice = 0 === $key ? $price : $basePrice;

                $percent = ($price / $basePrice - 1) * 100;
                $dateData = array_merge([
                    "date_point_{$key}" => (string) $date->setTimezone(config('app.timezone')),
                    "date_point_{$key}_percent" => $percent,
                ], $dateData);
            }

            $result = array_merge([
                'quote_add_coin' => $tradingPair->quote_coin,
                'base_add_coin' => $tradingPair->base_coin,
                'quote_analyzed_coin' => $pair->quote_coin,
                'base_analyzed_coin' => $pair->base_coin,
            ], $dateData);

            PriceOnAddNewsAboutAddPair::insert($result);
        }

        return $result;
    }

}
