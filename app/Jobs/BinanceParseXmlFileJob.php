<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Models\TradingPair;
use App\Models\HandledFiles;
use Cache;
use Carbon\CarbonInterval;

class BinanceParseXmlFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private TradingPair $tradingPair;

    public function __construct(private int $tradingPairId, private BinancePeriodVO $period)
    {
    }

    public function handle()
    {
        $this->tradingPair = TradingPair::find($this->tradingPairId);

        //        $code = $this->tradingPair->getTradingSpotPairCode();
        //        if (get_class($this->period) === BinanceDailyVO::class) {
        //            $like = "%$code-1m-____-__-__.csv%"; // ETCETH-1m-2020-12-06.csv
        //        } elseif (get_class($this->period) === BinanceMonthlyVO::class) {
        //            $like = "%$code-1m-____-__.csv%"; // ETCETH-1m-2020-12.csv
        //        }
        //        HandledFiles::where('file_name', 'like', $like)->update([
        //            'file_exists_on_binance' => 0,
        //        ]);

        $this->handlePair();
    }

    private function handlePair()
    {
        $xmlLink = $this->getXmlFilesListLink();

        $xml = Cache::remember($xmlLink, new CarbonInterval(weeks: 1), function () use ($xmlLink) {
            $xml = file_get_contents($xmlLink);

            return $xml;
        });

        $xml = $this->xmlToArray($xml);
        if (is_bool($xml)) {
            Cache::forget($xmlLink);
            throw new \Exception();
        }

        $contents = $xml['Contents'] ?? [];
        $contents = isset($contents['Name']) ? [$contents] : $contents;

        foreach ($contents as $content) {
            $this->handleContentAboutFile($content);
        }
    }

    private function handleContentAboutFile(array $content): void
    {
        $uri = $content['Key'];

        $chunks = explode('/', $uri);
        $fileName = array_pop($chunks);

        if (!str_ends_with($fileName, '.zip')) {
            return;
        }

        $csvFileName = str_replace('.zip', '.csv', $fileName);

        $handledFile = HandledFiles::where('file_name', $csvFileName)->first();
        if (!$handledFile) {
            $handledFile = HandledFiles::create([
                'file_name' => $csvFileName,
                'handled_success' => 0,
            ]);
        }
        $handledFile->setPeriod($this->period);
        $handledFile->file_exists_on_binance = 1;

        if ($this->period instanceof (new BinanceDailyVO) && $handledFile->monthlyFile()->doesntExist()) {
            // find monthly record for this month
            $chunks = explode('-', $handledFile->file_name);
            array_pop($chunks);

            $monthlyCsvFileName = implode('-', $chunks) . '.csv'; // example ETHBTC-1m-2017-01-01.zip

            //            var_dump($monthlyCsvFileName);
            $handledFileMonthly = HandledFiles::where('file_name', $monthlyCsvFileName)->first();
            if ($handledFileMonthly) {
                $handledFile->monthlyFile()->associate($handledFileMonthly)->save();
            }
        }

        $handledFile->save();
    }

    private function xmlToArray(string $xml): array|bool
    {
        $alertXml = simplexml_load_string(data: $xml, options: LIBXML_NOCDATA);

        return json_decode(json_encode($alertXml), true);
    }

    private function getXmlFilesListLink(): string
    {
        $host = "https://s3-ap-northeast-1.amazonaws.com";
        $pairCode = $this->tradingPair->getTradingSpotPairCode();

        $xmlLink = "$host/data.binance.vision?delimiter=/&prefix=data/spot/$this->period/klines/$pairCode/1m/";

        return $xmlLink;
    }
}
