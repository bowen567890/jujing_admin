<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\UserUsdt;
use App\Models\PoolConfig;
use App\Models\SignOrder;
use App\Models\NftConfig;

class FeeDividend extends Command
{
    protected $signature = 'FeeDividend';

    protected $description = '手续费分红';
    
    protected $userList = [];
    protected $usdtData = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'FeeDividend';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 1800);
        if ($lock)
        {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            $yday = date('Y-m-d', $time-86400);
            
            $total = SignOrder::query()
                ->where('date', $yday)
                ->sum('price');
            $total = @bcadd($total, '0', 2);
            if (bccomp($total, '0', 2)>0) 
            {
                $ordernum = get_ordernum();
                
                $NftConfig = NftConfig::GetListCache();
                foreach ($NftConfig as $conf) 
                {
                    if (bccomp($conf['fee_rate'], '0', 2)>0) 
                    {
                        $dividend = bcmul($total, $conf['fee_rate'], 6);
                        if (bccomp($dividend, '0', 6)>0) 
                        {
                            $ulist = User::query()
                                ->where('nft_rank', $conf['lv'])
                                ->get(['id','nft_rank'])
                                ->toArray();
                            if ($ulist) 
                            {
                                $count = count($ulist);
                                $avg = bcdiv($dividend, $count, 6);
                                if (bccomp($avg, '0', 6)>0) 
                                {
                                    foreach ($ulist as $uval) 
                                    {
                                        $user_id = $uval['id'];
                                        //防止重复 比如用户中途升级
                                        if (!isset($this->userList[$user_id]))
                                        {
                                            $this->userList[$user_id] = [
                                                'user_id' => $user_id,
                                                'usdt' => $avg
                                            ];
                                            
                                            //分类1系统增加2系统扣除3余额提币4提币驳回5团队返利GAS6手续费分红
                                            $this->usdtData[] = [
                                                'ordernum' => $ordernum,
                                                'user_id' => $uval['id'],
                                                'from_user_id' => 0,
                                                'type' => 1,
                                                'cate' => 6,
                                                'total' => $avg,
                                                'msg' => '手续费分红',
                                                'content' => "手续费分红{$conf['lv']}",
                                                'created_at' => $datetime,
                                                'updated_at' => $datetime,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if ($this->userList) {
                foreach ($this->userList as $uval) {
                    User::query()->where('id', $uval['user_id'])->increment('usdt', $uval['usdt']);
                }
            }
            
            if ($this->usdtData) {
                $usdtData = array_chunk($this->usdtData, 1000);
                foreach ($usdtData as $ndata) {
                    UserUsdt::query()->insert($ndata);
                }
            }
            
            $MyRedis = new MyRedis();
            $MyRedis->del_lock($lockKey);
        }
    }
    
    public function setUserList($user_id = 0)
    {
        if (!isset($this->userList[$user_id]))
        {
            $this->userList[$user_id] = [
                'user_id' => $user_id,
                'usdt' => '0'
            ];
        }
    }
}
