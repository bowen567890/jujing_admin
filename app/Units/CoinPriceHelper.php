<?php


namespace App\Units;


use App\Models\MainCurrency;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

class CoinPriceHelper
{

    private $client;

    private $base_uri = 'https://api.binance.com';

    private $hr24_url = '/api/v3/ticker/24hr';

    private $symbolArr = [
//        'SHIBUSDT',
//        'ETHUSDT'
    ];


    public function __construct(){
        $currency = MainCurrency::query()->where('name','<>','SCC')->where('name','<>','USDT')->pluck('name')->toArray();
        foreach ($currency as $item){
            $this->symbolArr[$item] = $item.'USDT';
        }
        $this->client = new Client([
            'base_uri' => $this->base_uri,
            'timeout' => 2.0
        ]);
    }

    /**
     * 获取所有比价从币安接口
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAllPriceByBinance(){
        foreach ($this->symbolArr as $currency=>$symbol){
            try {
                $response = $this->client->get($this->hr24_url.'?symbol='.$symbol);
                $result = json_decode($response->getBody(),true);

                MainCurrency::query()->where('name',$currency)->update([
                    'rate' => $result['lastPrice']
                ]);
            }catch (\Exception $e){

            }
        }
    }

}
