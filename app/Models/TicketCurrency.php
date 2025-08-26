<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

class TicketCurrency extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'ticket_currency';
    
    
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
}
