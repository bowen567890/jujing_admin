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

class SyncTokenPrice extends Command
{

    // 自定义脚本命令签名
    protected $signature = 'sync:tokenprice';

    // 自定义脚本命令描述
    protected $description = '同步薄饼代币价格';


    // 创建一个新的命令实例
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $list = MainCurrency::query()
            ->where('id', '<>', 1)   //1USDT2MH3VV4BNB
            ->where('is_sync', '=', 1)
            ->orderBy('id', 'desc')
            ->get(['id','contract_address','contract_address_lp','pancake_cate'])
            ->toArray();
        if ($list)
        {
            $client = new Client();
            $usdtContractAddress = env('USDT_ADDRESS');
            $busdContractAddress = env('BUSD_ADDRESS');
            $wbnbContractAddress = env('WBNB_ADDRESS');
            
            foreach ($list as $val)
            {
                //不是SPACEX
                if (!in_array($val['id'], [2])) 
                {
                    try
                    {
                        $contract_address = $val['contract_address_lp'];
                        $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                            'form_params' => [
                                'contract_address' => $contract_address
                            ],
                            'timeout' => 10,
                            'verify' => false
                        ]);
                        $result = $response->getBody()->getContents();
                        if (!is_array($result)) {
                            $result = json_decode($result, true);
                        }
                        
                        if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                            !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']) ||
                            !isset($result['data']['token0']) || !isset($result['data']['token1']))
                        {
                            MainCurrency::query()->where('id', $val['id'])->update(['is_success'=>0]);
                            Log::channel('lp_info')->info('查询LP信息V2失败');
                        }
                        else
                        {
                            $token0 = strtolower($result['data']['token0']);
                            $token1 = strtolower($result['data']['token1']);
                            if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                                $coin_price = @bcdiv($result['data']['reserve1'], $result['data']['reserve0'], 10);
                            } else {
                                $coin_price = @bcdiv($result['data']['reserve0'], $result['data']['reserve1'], 10);
                            }
                            
                            if (bccomp($coin_price, '0', 10)>0) {
                                MainCurrency::query()->where('id', $val['id'])->update(['rate'=>$coin_price,'is_success'=>1]);
                            } else {
                                MainCurrency::query()->where('id', $val['id'])->update(['is_success'=>0]);
                            }
                        }
                    }
                    catch (\Exception $e)
                    {
                        MainCurrency::query()->where('id', $val['id'])->update(['is_success'=>0]);
                        Log::channel('lp_info')->info('查询LP信息V2失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
                    }
                } 
                else 
                {
                    //SPACEX价格路径  VV->WBNB->USDT
                    if ($val['id']==2) 
                    {
                        $coin_price = $this->getSpacexPrice2();
                        if (bccomp($coin_price, '0', 10)<=0) {
                            $coin_price = $this->getSpacexPrice1();
                        }
                        if (bccomp($coin_price, '0', 10)>0) {
                            MainCurrency::query()->where('id', $val['id'])->update(['rate'=>$coin_price,'is_success'=>1]);
                        } else {
                            MainCurrency::query()->where('id', $val['id'])->update(['is_success'=>0]);
                        }
                    }
                }
            }
        }
    }
    
    
    public function handle_old()
    {
        $token1 = '0xfa9aa7fb5a781f35127944dcca50ca492d829c9c'; //FIL 正式
        $token1Decimals = 18;
        $token2 = env('USDT_ADDRESS');     //Usdt
        $token2Decimals = 18;
        $queryData = [
            'token1' => $token1,
            'token2' => $token2,
            'token3' => '',
            'token1Decimals' => $token1Decimals,
            'token2Decimals' => $token2Decimals,
            'token3Decimals' => '',
        ];
        
        $quotePrice = $this->searchPrice($queryData);
        if ($quotePrice===false)
        {
            Log::channel('sync_price')->info('获取代币价格失败');
        }
        else
        {
            $coin_price = @bcadd($quotePrice, 0, 7);
            if (bccomp($coin_price, 0, 7)>0) {
                Config::where('key', 'ma_usdt_price')->update([
                    'value' => $coin_price
                ]);
                Cache::put('config:ma_usdt_price',$coin_price);
            }
            Log::channel('sync_price')->info('获取代币价格成功');
        }
    }
    
    
    public function handle222()
    {
        $list = MainCurrency::query()
            ->where('contract_address', '<>', env('USDT_ADDRESS'))
            ->get()
            ->toArray();
        
        if ($list) 
        {
            foreach ($list as $val) 
            {
                if ($val['contract_address']=='0x7130d2a12b9bcbfae4f2634d864a1ee1ce3ead9c') 
                {
                    $token1 = $val['contract_address'];
                    $token1Decimals = $val['precision'];
                    $token2 = env('BUSD_ADDRESS');
                    $token2Decimals = 18;
                    $token3 = env('USDT_ADDRESS');
                    $token3Decimals = 18;
                    
                    $queryData = [
                        'token1' => $token1,
                        'token2' => $token2,
                        'token3' => $token3,
                        'token1Decimals' => $token1Decimals,
                        'token2Decimals' => $token2Decimals,
                        'token3Decimals' => $token3Decimals,
                    ];
                } 
                else 
                {
                    $token1 = $val['contract_address'];
                    $token1Decimals = $val['precision'];
                    $token2 = env('USDT_ADDRESS');     //Usdt
                    $token2Decimals = 18;
                    $queryData = [
                        'token1' => $token1,
                        'token2' => $token2,
                        'token3' => '',
                        'token1Decimals' => $token1Decimals,
                        'token2Decimals' => $token2Decimals,
                        'token3Decimals' => '',
                    ];
                }
                
                $quotePrice = $this->searchPrice($queryData);
                if ($quotePrice===false)
                {
                    Log::channel('sync_price')->info('获取代币价格失败');
                }
                else
                {
                    $old_rate = $val['rate'];
                    $coin_price = @bcadd($quotePrice, 0, 6);
                    if (bccomp($coin_price, 0, 6)>0) {
                        MainCurrency::query()->where('id', $val['id'])->update(['rate'=>$coin_price, 'old_rate'=>$old_rate]);
                    }
                    Log::channel('sync_price')->info('获取代币价格成功');
                }
            }
        }
    }
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function searchPrice($queryData)
    {
        try 
        {
            $path[] = $queryData['token1'];
            $path[] = $queryData['token2'];
            if ($queryData['token3']) {
                $path[] = $queryData['token3'];
            }
            
            $token1Decimals = $queryData['token1Decimals'];
            $token2Decimals = $queryData['token2Decimals'];
            $token3Decimals = $queryData['token3Decimals'];
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/getCoinPrice',[
                'json' => [
                    'route_address' => '0x10ed43c718714eb63d5aa57b78b54704e256024e',    //固定不变
                    'amount_in_decimals' => $token1Decimals,
                    'path' => $path
                ]
            ]);
            $result = json_decode($response->getBody()->getContents(),true);
            $price =  empty($result['data']) ? 0 : number_format($result['data'][count($result['data'])-1], $token2Decimals, '.', '');
            return sprintf('%.10f',$price/pow(10,$token2Decimals));
        }catch (\Exception $e){
            return false;
        }
    }
    
    public function getSpacexPrice1()
    {
        $price = '0';
        try
        {
            $wbnbContractAddress = env('WBNB_ADDRESS');
            $contract_address = env('SPACEX_ADDRESS_LP');   //SPACEX|WBNB LP合约地址
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address
                ],
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']) ||
                !isset($result['data']['token0']) || !isset($result['data']['token1'])
                )
            {
                Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败');
            }
            else
            {
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                
                //查询BNB|USDT 价格
                $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                if (bccomp($bnbUsdtPrice, '0', 10)>0)
                {
                    if ($token1==$wbnbContractAddress)
                    {
                        $usdtNum = bcmul($result['data']['reserve1'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve0'], 10);
                    } else {
                        $usdtNum = bcmul($result['data']['reserve0'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve1'], 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        
        return $price;
    }
    
    public function getSpacexPrice2()
    {
        $price = '0';
        try
        {
            $bnbAddress = env('WBNB_ADDRESS');
            $spacexAddress = env('SPACEX_ADDRESS');
            $url = "https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token={$bnbAddress}&to_token={$spacexAddress}&chain=bsc&max_hops=3&max_routes=6&protocol=v3";
            
            $client = new Client();
            $response = $client->get($url, [
                'timeout' => 10,
                'verify' => false
            ]);
            
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['status']) || $result['status']!=1 ||
                !isset($result['data']) || !is_array($result['data']) || !$result['data'] ||
                !isset($result['data'][0]) || !is_array($result['data'][0]) || !$result['data'][0] ||
                !isset($result['data'][0]['pair_path']) || !is_array($result['data'][0]['pair_path']) || !$result['data'][0]['pair_path'] ||
                !isset($result['data'][0]['pair_path'][0]) || !is_array($result['data'][0]['pair_path'][0]) || !$result['data'][0]['pair_path'][0] ||
                !isset($result['data'][0]['pair_path'][0]['token_in']) || !isset($result['data'][0]['pair_path'][0]['token_out']) ||
                !isset($result['data'][0]['pair_path'][0]['reserve_in']) || !isset($result['data'][0]['pair_path'][0]['reserve_out']) ||
                !$result['data'][0]['pair_path'][0]['token_in'] || !$result['data'][0]['pair_path'][0]['token_out']
            )
            {
                Log::channel('ave_price')->info('查询SPACEX价格失败');
            }
            else
            {
                $addressArr = [
                    $bnbAddress,
                    $spacexAddress
                ];
                $token_in = strtolower($result['data'][0]['pair_path'][0]['token_in']);
                $token_out = strtolower($result['data'][0]['pair_path'][0]['token_out']);
                if (!in_array($token_in, $addressArr) || !in_array($token_out, $addressArr)) {
                    Log::channel('ave_price')->info('查询SPACEX价格失败');
                }
                else
                {
                    $reserve_in = $result['data'][0]['pair_path'][0]['reserve_in'];
                    $reserve_out = $result['data'][0]['pair_path'][0]['reserve_out'];
                    $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                    
                    if ($token_in==$bnbAddress) {
                        $usdtNum = bcmul($reserve_in, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    } else {
                        $usdtNum = bcmul($reserve_out, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('ave_price')->info('查询SPACEX价格失败',['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        return $price;
    }
    
}
