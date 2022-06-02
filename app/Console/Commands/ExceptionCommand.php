<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;
use HeadlessChromium\BrowserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

class ExceptionCommand extends Command
{
    protected $signature = 'throw:exception';

    protected $description = 'Command description';

    public function handle()
    {
//        $this->guz();
//        //        $this->proxyCheck();
//        die;


        $url =
            'https://www.google.com/search?q=luna&sxsrf=ALiCzsYzgdEDh5m51KvTK77jYYYy7_Po4Q:1654069763584&source=lnt&tbs=qdr:h&sa=X&ved=2ahUKEwiotMSF4ov4AhUWvYsKHVLfCTsQpwV6BAgBEBU&biw=917&bih=828&dpr=1.13';

        $imgP = base_path('img.png');

        $c = storage_path('chrome/chrome');
        $browserFactory = new BrowserFactory($c);
        $browserFactory->setOptions([
            //            'windowSize'   => [1920, 1080],
            'noSandbox' => true,
//            'proxyServer' => '129.159.148.90:3128', // need test IP:LOGIN:PASSWORD
        ]);
        // starts headless chrome
        $browser = $browserFactory->createBrowser();

        try {
            // creates a new page and navigate to an URL
            $page = $browser->createPage();


            //$url = 'https://www.google.com/search?q=puppeter+laravel+9';
            $url = 'https://www.myip.com/';
            $page->navigate($url)->waitForNavigation();

            // screenshot - Say "Cheese"! 😄
            //            $page->screenshot()->saveToFile($imgP);
            $html = $page->getHtml();
        } finally {
            // bye
            $browser->close();
        }

//        file_put_contents(base_path('text_chrome.html'), $html);
        dd($html);


        dd(file_get_contents('https://www.google.com/alerts/feeds/02384417501511553911/13709992515300506746'));
        throw new \Exception();
    }

    private function runBrowserHot()
    {
        $b = new Browsershot();
        $b->windowSize(1920, 1080)
            //            ->setProxyServer()
          ->noSandbox()
          ->setUrl($url);

        $b->setOption('functionPolling', Browsershot::POLLING_REQUEST_ANIMATION_FRAME);
        $b->setOption('functionTimeout', 20);

        $b->save($imgP);


        $html = $b->bodyHtml(); // returns the html of the body
    }

    private function proxyCheck()
    {
        $p = [
            '165.225.80.237:10605',
            '170.83.79.105:999',
            '147.75.68.201:80',
            '41.45.139.31:80',
            '129.159.148.90:3128',
            '147.161.166.246:10098',
            '165.225.196.90:10605',
            '165.225.204.100:10605',
            '165.225.80.226:10605',
            '165.225.240.100:10605',
            '74.82.50.155:3128',
            '41.193.84.196:3128',
            '147.161.167.4:10022',
            '165.225.80.215:10605',
            '165.225.27.21:10011',
            '165.225.24.16:10185',
            '165.225.26.108:10605',
            '165.225.210.85:10605',
            '165.225.24.53:10698',
            '165.225.26.116:10605',
            '165.225.24.51:10227',
            '165.225.26.80:10605',
            '165.225.24.53:10388',
            '165.225.76.178:10605',
            '165.225.204.103:10605',
            '165.225.20.107:10605',
            '165.225.242.106:10605',
            '165.225.204.16:10605',
            '41.128.148.77:1981',
            '165.225.196.73:10605',
            '165.225.204.248:10007',
            '165.225.194.33:10012',
            '165.225.76.64:10605',
            '165.225.12.114:10605',
            '165.225.76.32:10605',
        ];


        $linii = $p;               // Get each proxy

        for ($i = 0; $i < count($linii) - 1; $i++) {
            $this->test($linii[$i]);
        } // Test each proxy


    }


    private function guz()
    {
        $client = new Client(['timeout' => 0.1]);

        $promises = [];

        for ($i = 1; $i <= 1; $i++) {
            try {
                $promise = $client->requestAsync(
                    'GET',
                    'http://checkip.dyndns.org', [
                    'proxy' => '129.159.148.90:3128',
                    //                                                'timeout' => 1,
                ],
                );
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                // echo $e->getMessage();
                echo "this mean bad proxy or timeout";
                exit;
            }

            $promises[] = $promise;
        }

        $results = \GuzzleHttp\Promise\Utils::unwrap($promises);
        $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        foreach ($results as $result) {
            // var_dump($result['value']);
            // print_r($result['value']);
            var_dump($result['value']->getStatusCode());;
            //echo $result['value']->getBody();
        }
    }


    private function test($proxy)
    {
        $splited = explode(':', $proxy); // Separate IP and port
        if ($con = @fsockopen($splited[0], $splited[1], $eroare, $eroare_str, 3)) {
            var_dump($proxy);               // Check if we can connect to that IP and port
            print $proxy . '<br>';          // Show the proxy
            fclose($con);                   // Close the socket handle
        }
    }
}
