<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Enum\BinancePeriodVO;
use App\Enum\BinanceDailyVO;
use App\Services\BinanceLinkHelper;
use App\Models\TradingPair;
use App\Models\HandledFiles;

class BinanceParseXmlFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected BinanceLinkHelper $binanceLinkHelper;

    private TradingPair $tradingPair;

    public function __construct(private int $tradingPairId, private BinancePeriodVO $period)
    {
        //
    }

    public function handle()
    {
        $this->tradingPair = TradingPair::find($this->tradingPairId);
        $this->binanceLinkHelper = new BinanceLinkHelper();

        $this->handlePair();
    }

    private function handlePair()
    {
        $this->binanceLinkHelper->setTradingPair($this->tradingPair);
        $this->binanceLinkHelper->setPeriod($this->period);

        $xmlLink = $this->binanceLinkHelper->getXmlFilesListLink();
        $xml = file_get_contents($xmlLink);
        $xml = $this->xmlToArray($xml);

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
        $handledFile->file_exists_on_binance = true;

        if ($this->period instanceof (new BinanceDailyVO) && $handledFile->monthlyFile()->doesntExist()) {
            // find monthly record for this month
            $chunks = explode('-', $handledFile->file_name);
            array_pop($chunks);

            $monthlyCsvFileName = implode('-', $chunks) . '.csv'; // example ETHBTC-1m-2017-01-01.zip

            var_dump($monthlyCsvFileName);
            $handledFileMonthly = HandledFiles::where('file_name', $monthlyCsvFileName)->first();
            if ($handledFileMonthly) {
                $handledFile->monthlyFile()->associate($handledFileMonthly);
            }
        }

        if ($handledFile->isDirty()) {
            $handledFile->save();
        }
    }

    private function xmlToArray(string $xml): array
    {
        $alertXml = simplexml_load_string(data: $xml, options: LIBXML_NOCDATA);

        return json_decode(json_encode($alertXml), true);
    }
}
