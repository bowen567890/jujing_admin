<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MainCurrency extends Model
{
	use HasDateTimeFormatter;


    protected $table = 'main_currency';

    /**
     * 获取BHB兑U汇率
     * @return mixed
     */
    public static function getBhbRate(){
        return self::query()->where('name','SCC')->value('rate');
    }

    /**
     * 根据方案和类型
     * @param $invest
     * @param $num
     * @param int $p_type
     * @return array
     */
    public static function getPledgePowerByInvest($invest,$num,$p_type){
        $otherRate = $invest->otherCoinDb->rate;
        $coin = $invest->mainCoinDb;
        if ($p_type==1){
            $power = bcdiv(bcmul($num,$coin->rate),0.7);
            $acPower = bcmul($power,$invest->seven_rate/100);
            $bhbNum = bcdiv(bcmul($power,0.3),$otherRate);
        }elseif ($p_type==2){
            $power = bcdiv(bcmul($num,$coin->rate),0.6);
            $acPower = bcmul($power,$invest->six_rate/100);
            $bhbNum = bcdiv(bcmul($power,0.4),$otherRate);
        }elseif ($p_type==3){
            $power = bcdiv(bcmul($num,$coin->rate),0.5);
            $acPower = bcmul($power,$invest->five_rate/100,9);
            $bhbNum = bcdiv(bcmul($power,0.5),$otherRate,9);
        }else{
            $power = bcmul($otherRate,$num);
            $acPower = bcmul($power,$invest->zero_rate/100,9);
            $bhbNum = $num;
        }
        return compact('bhbNum','power','acPower');
    }


    public static function getDestroyPowerByInvest($invest,$num){
        $bhbRate = $invest->coin->rate;
        $power = bcmul($bhbRate,$num);
        $acPower = bcmul($power,$invest->rate/100,9);
        $bhbNum = $num;
        return compact('bhbNum','power','acPower');
    }


    public static function getLpPowerByInvest($invest,$num){
        $bhbPrice = self::query()->where('name','LP')->value('rate');
        $client = new Client();
        $response = $client->post('http://127.0.0.1:9090/api/wallet/pro/getSwapInfo',[
            'form_params' => [
                'mainChain' => 'BNB',
                'contractAddress' => MainCurrency::query()->where('name','LP')->value('contract_address'),
            ]
        ]);
        $lpResponse = json_decode($response->getBody()->getContents(),true);

        $a= number_format($lpResponse['obj']['reserve1']/$lpResponse['obj']['totalSupply'], 9, '.', '');
        $b = bcmul($num,2,9);
        $power = bcdiv(bcmul($b,$a,9),$bhbPrice,9);
        $acPower = bcmul($power,$invest->rate/100,9);
        $bhbNum = $num;
        return compact('bhbNum','power','acPower');
    }
    
    public function getLpInfo($contract_address)
    {
        try
        {
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']) ||
                !isset($result['data']['token0']) || !isset($result['data']['token1']))
            {
                return false;
            }
            else
            {
                return $result['data'];
            }
            
        }
        catch (\Exception $e)
        {
            return false;
        }
    }
    
    public function getLpInfov3($contract_address)
    {
        try
        {
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lp3Info',[
                'form_params' => [
                    'contract_address' => $contract_address,
                    //'is_fan' => $is_fan  // 查询token1 转 token2 价格  is_fan = 1  否则传递 2
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['amountOut']) ||
                !isset($result['data']['token0']) || !isset($result['data']['token1']))
            {
                return false;
            }
            else
            {
                return $result['data'];
            }
            
        }
        catch (\Exception $e)
        {
            return false;
        }
    }
    
    /**
     * 自动买币根据 订单号查询
     */
    public function getTransactionDetail($ordernum='')
    {
        $amount = '0';
        if ($ordernum) 
        {
            try
            {
                $client = new Client();
                $response = $client->post('127.0.0.1:9099/getTransactionDetail',[
                    'form_params' => [
                        'contract_address' => env('RECHARGE_CONTRACT_ADDRESS'),   //查询自动买币的充值合约地址
                        'order_no' => $ordernum,   
                    ],
                    'timeout' => 10,
                    'verify' => false
                ]);
                $result = $response->getBody()->getContents();
                if (!is_array($result)) {
                    $result = json_decode($result, true);
                }
                if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                    !isset($result['data']) || !isset($result['data']['out_num']))
                {
                    Log::channel('auto_trade_detail')->info('查询自动买币信息失败', $result);
                }
                else
                {
                    $pows = pow(10,18);
                    $amount = @bcadd($result['data']['out_num'], '0', 6);
                    if (bccomp($amount, '0', 6)>0) {
                        $amount = bcdiv($amount, $pows, 6);    //钱包系统返回来要除以18位
                    }
                }
            }
            catch (\Exception $e)
            {
                Log::channel('auto_trade_detail')->info('查询自动买币信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
            }
        }
        return $amount;
        
    }
    
    /**
     * 老的接口 已经废弃
     */
    public function getAutoTradeDetail($hash)
    {
        $amount = 0;
        if ($hash)
        {
            try
            {
                $client = new Client();
                $response = $client->post('http://127.0.0.1:9090/v1/bnb/getAutoTradeDetail',[
                    'form_params' => [
                        'hash' => $hash
                    ],
                    'timeout' => 10,
                    'verify' => false
                ]);
                $result = $response->getBody()->getContents();
                if (!is_array($result)) {
                    $result = json_decode($result, true);
                }
                if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                    !isset($result['data']) || !isset($result['data']['amount']))
                {
                    Log::channel('auto_trade_detail')->info('查询自动买币信息失败', $result);
                }
                else
                {
                    $amount = @bcadd($result['data']['amount'], '0', 6);
                }
            }
            catch (\Exception $e)
            {
                Log::channel('auto_trade_detail')->info('查询自动买币信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
            }
        }
        return $amount;
        
    }

}
