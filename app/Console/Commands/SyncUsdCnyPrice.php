<?php
namespace App\Console\Commands;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Config;
use App\Models\MainCurrency;

class SyncUsdCnyPrice extends Command
{

    // 自定义脚本命令签名
    protected $signature = 'sync:UsdCnyPrice';

    // 自定义脚本命令描述
    protected $description = '同步USD|CNY价格';


    // 创建一个新的命令实例
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
//         $response = $client->get('http://op.juhe.cn/onebox/exchange/currency?from=USD&to=CNY&version=2&key=ac9d5d5702a00281498854cc29931603');
        $response = $client->get('https://sapi.k780.com/?app=finance.rate&scur=USD&tcur=CNY&appkey=75762&sign=11f396891e124dc5d48985a9f88ceb5e');
        $result = $response->getBody()->getContents();
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }
        
        if ($result && is_array($result) && isset($result['success']) && $result['success']==1 && 
            isset($result['result']) && $result && is_array($result) && isset($result['result']['rate'])
        ) 
        {
            $exchange = @bcadd($result['result']['rate'], '0', 2);
            if ($exchange>0) {
                Config::query()->where('key','usdt_cny_rate')->update(['value'=>$exchange]);
            }
        }
    }
}
