<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\GoogleAlertsNews;
use App\Models\Coin;
use Log;
use DB;
use Exception;

class ParseGoogleAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $coinId,
    ) {
    }

    public function handle()
    {
        Log::info('Start handle ' . static::class);

        $coin = Coin::find($this->coinId);
        $this->handleCoinAlert($coin);
    }

    private function handleCoinAlert(Coin $coin): void
    {
        try {
            $xml = file_get_contents($coin->google_alerts_url);
        } catch (Exception $e) {
            DB::table('jobs')->where('queue', 'google:parse-alerts')->delete();

            throw $e;
        }

        $alerts = $this->xmlToArray($xml);

        $entries = $alerts['entry'] ?? [];
        $entries = isset($entries['id']) ? [$entries] : $entries;

        foreach ($entries as $entry) {
            $googleAlertNews = $this->parseAlertEntry($entry);
            $coin->googleAlertsNews()->syncWithoutDetaching($googleAlertNews);
        }
    }

    private function parseAlertEntry(array $entry): GoogleAlertsNews
    {
        $url = $this->parseEntryUrl($entry);
        $googleAlertNews = GoogleAlertsNews::where('url', $url)->first();
        if (!$googleAlertNews) {
            $data = [
                'title' => is_string($entry['title']) ? $entry['title'] : '',
                'url' => $url,
                'content' => is_string($entry['content']) ? $entry['content'] : '',
                'news_published_at' => Carbon::parse($entry['published']),
                'news_updated_at' => Carbon::parse($entry['updated']),
            ];
            $googleAlertNews = GoogleAlertsNews::create($data);
        }

        return $googleAlertNews;
    }

    private function parseEntryUrl(array $entry): string
    {
        $rawLink = $entry['link']['@attributes']['href'];
        $query = parse_url($rawLink)['query'];
        parse_str($query, $queryParams);

        return $queryParams['url'];
    }

    private function xmlToArray(string $xml): array
    {
        $alertXml = simplexml_load_string(data: $xml, options: LIBXML_NOCDATA);

        return json_decode(json_encode($alertXml), true);
    }
}
