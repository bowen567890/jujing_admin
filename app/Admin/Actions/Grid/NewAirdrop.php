<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MainCurrency;
use App\Models\AirdropStage;
use App\Models\AirdropUser;
use App\Models\MyRedis;
use App\Models\User;
use App\Models\BitQuery;

class NewAirdrop extends AbstractTool
{
    /**
     * @return string
     */
    protected $title = '空投快照';
    protected $style = 'btn btn-primary';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $lockKey = 'command:AddressBalance';
        $MyRedis = new MyRedis();
//                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 1800);
        if ($lock)
        {
            //空投统计
            set_time_limit(0);
            ini_set('memory_limit','2048M');
            //             $contractAddress = config('gr_contract_address');
            $contractAddress = MainCurrency::query()->where('name', 'IDO')->value('contract_address');
            //             $contractAddress = '0xd6b4a3ee4c670bb09f91737feb19752d95e0622a';
            
            $bigInfo = BitQuery::query()
                ->orderBy('num', 'asc')
                ->orderBy('id', 'asc')
                ->first();
            $apiKey = $bigInfo->bigkey;
            BitQuery::query()->where('id', $bigInfo->id)->increment('num', 1);
            
            User::select(['id','wallet'])
            ->chunk(200, function ($walletList) use ($contractAddress, $apiKey)
            {
                $walletList = $walletList->toArray();
                if ($walletList)
                {
                    $walletList = array_column($walletList, 'wallet');
                    $walletStr = json_encode($walletList);
                    //查询所有注册地址余额
                    $qry = ['query' => 'query GetHolders {
                    ethereum(network: bsc) {
                        address(
                            address: {in: '.$walletStr.'}
                            ) {
                                address
                                balances(currency: {is: "'.$contractAddress.'"}) {
                                    value
                                }
                            }
                        }
                    }'
                    ];
                    
                    try
                    {
                        $client = new Client([
                            'verify' =>false
                        ]);
                        $response = $client->post('https://graphql.bitquery.io',[
                            'headers' => [
                                'Connection' => 'keep-alive',
                                'User-Agent' => 'PostmanRuntime/7.28.4',
                                'Content-Type' => 'application/json',
                                'origin' => 'https://pancakeswap.finance',
                                'referer' => 'https://graphql.bitquery.io/ide',
                                'X-API-KEY' => $apiKey,
                            ],
                            'json' => $qry
                        ]);
                        $result = json_decode($response->getBody()->getContents(),true);
                        if ($result && is_array($result) && isset($result['data'])
                            && isset($result['data']['ethereum']) && isset($result['data']['ethereum']['address'])
                            && is_array($result['data']['ethereum']['address']) && $result['data']['ethereum']['address'])
                        {
                            $sync_time = date('Y-m-d H:i:s');
                            $valueList = [];
                            $addressList = $result['data']['ethereum']['address'];
                            foreach ($addressList as $val)
                            {
                                if (isset($val['address']) && $val['address'])
                                {
                                    $wallet = strtolower($val['address']);
                                    $user = User::query()->where('wallet', $wallet)->first();
                                    if ($user)
                                    {
                                        if (isset($val['balances']) && is_array($val['balances']) && isset($val['balances'][0]) && isset($val['balances'][0]['value'])) {
                                            $user->hold_iddao = @bcadd($val['balances'][0]['value'], '0', 6);
                                            //                                             $user->sync_num = $user->sync_num+1;
                                            $user->save();
                                        } else {
                                            //                                             $user->sync_num = $user->sync_num+1;
                                            //                                             $user->save();
                                        }
                                    }
                                }
                            }
                            Log::channel('sync_address_balance')->info('获取持币地址余额成功');
                        } else {
                            Log::channel('sync_address_balance')->info('获取持币地址余额失败');
                        }
                    }catch (\Exception $e){
                        Log::channel('sync_address_balance')->info('获取持币地址余额失败'.$e->getMessage());
                    }
                }
            });
            
            //空投统计
            $this->airdrop();
            
            
//             $MyRedis = new MyRedis();
            //         $MyRedis->del_lock($lockKey);
            return $this
                ->response()
                ->success('操作成功')
                ->refresh();
        } 
        else 
        {
            return $this->response()->error('操作频繁');
        }
    }

    /**
     * @return string|void
     */
    protected function href()
    {
        // return admin_url('auth/users');
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		return ['空投快照', '此操作将生成空投快照'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
    
    //空投统计
    public function airdrop()
    {
        $time = time();
        $date = date('Y-m-d', $time);
        $dateTime = date('Y-m-d H:i:s', $time);
        //空投期数
        $whole_weight = '0';
        $whole_iddao = User::query()->sum('hold_iddao');
        $whole_iddao = @bcadd($whole_iddao, '0', 6);
        
        $whole_ths = User::query()->sum('ths');
        $whole_ths = @bcadd($whole_ths, '0', 6);
        
        $airdrop_total = @bcadd(config('airdrop_total'), '0', 2);
        
        $AirdropStage = new AirdropStage();
        $AirdropStage->airdrop_coin = config('airdrop_coin');
        $AirdropStage->airdrop_total = $airdrop_total;
        $AirdropStage->airdrop_date = config('airdrop_date');
        $AirdropStage->whole_weight = $whole_weight;
        $AirdropStage->whole_iddao = $whole_iddao;
        $AirdropStage->whole_ths = $whole_ths;
        $AirdropStage->date = $date;
        $AirdropStage->save();
        $stage_id = $AirdropStage->id;
        
        //获取持币大于0的用户
        $userList = User::query()
        //             ->where('hold_iddao', '>', 0)
        ->orderBy('id', 'asc')
        ->get(['id','ths','hold_iddao','nft_node'])
        ->toArray();
        
        if ($userList)
        {
            //权重算法简化下
            //权重算法调整为：算力×2+持币量（nft由10调整为2）
            
            $airList = [];
            foreach ($userList as $val)
            {
                
                if (bccomp($val['ths'], '0', 6)>0 || bccomp($val['hold_iddao'], '0', 6)>0)
                {
                    $my_weight = '0';
                    
                    $nft_node = $val['nft_node']==0 ? '1' : '2';
                    $xisu = bcmul($val['ths'], $nft_node, 6);
                    $my_weight = @bcadd($xisu, $val['hold_iddao'], 6);
                    
                    $airList[] = [
                        'stage_id' => $stage_id,
                        'user_id' => $val['id'],
                        'ths' => $val['ths'],
                        'hold_iddao' => $val['hold_iddao'],
                        'nft_node' => $val['nft_node'],
                        'my_weight' => $my_weight,
                        'date' => $date,
                        'created_at' => $dateTime,
                        'updated_at' => $dateTime,
                    ];
                    $whole_weight = bcadd($whole_weight, $my_weight, 6);
                }
            }
            
            AirdropStage::query()->where('id', $stage_id)->update(['whole_weight'=>$whole_weight]);
            
            $airList = array_chunk($airList, 1000);
            foreach ($airList as $data) {
                AirdropUser::query()->insert($data);
            }
            
            //分配 个人权重/全网权重*分配量=个人所有量
            $dropList = AirdropUser::query()
                ->where('stage_id', $stage_id)
                ->get(['id', 'my_weight'])
                ->toArray();
            foreach ($dropList as $user) {
                $fpRate = bcdiv($user['my_weight'], $whole_weight, 6);
                $estimate = bcmul($airdrop_total, $fpRate, 6);
                AirdropUser::query()->where('id', $user['id'])->update(['estimate'=>$estimate]);
            }
        }
    }
}
