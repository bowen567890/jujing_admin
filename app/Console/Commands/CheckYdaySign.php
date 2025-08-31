<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\NodePool;
use App\Models\UserUsdt;
use App\Models\InsuranceOrder;
use App\Models\RankConfig;
use App\Models\PoolConfig;
use App\Models\UserLockOrder;
use App\Models\SignOrder;

class CheckYdaySign extends Command
{
    protected $signature = 'CheckYdaySign';

    protected $description = '检查昨日签到';

    protected $userList = [];
    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'CheckYdaySign';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 3600);
        if ($lock)
        {
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            $yday = date('Y-m-d', $time-86400);
            
            $list = UserLockOrder::query()
                ->where('status', 0)    //释放状态0释放中1已完结
                ->get(['id','user_id','total','wait_num','over_num','actual_num','total_day','wait_day','ordernum'])
                ->toArray();
            if ($list)
            {
                //销毁比率
                $destroy_rate = @bcadd(config('not_sign_destroy_rate'), '0', 2);
                $destroy_rate = bccomp($destroy_rate, '1', 2)>=0 ? '1' : $destroy_rate;
                $destroy_rate = bccomp($destroy_rate, '0', 2)<=0 ? '0' : $destroy_rate;
//                 $market_rate = bcsub('1', $destroy_rate, 2);  //营销池比率
                
                //锁仓释放
                $zhi_lock_rate = @bcadd(config('zhi_lock_rate'), '0', 6);
                
                $destroyPool = $marketPool = '0';
                
                foreach ($list as $lval)
                {
                    if (!isset($this->userList[$lval['user_id']])) 
                    {
                        $this->setUserList($lval['user_id']);
                        $flag = SignOrder::query()->where('user_id', $lval['user_id'])->where('date', $yday)->exists();
                        $this->userList[$lval['user_id']]['yday_sign'] = $flag;
                        $this->userList[$lval['user_id']]['parent_id'] = User::query()->where('id', $lval['user_id'])->value('parent_id');
                    }
                    
                    //未签到 → 当日奖励N% 销毁 + N% 落入营销池
                    if (!$this->userList[$lval['user_id']]['yday_sign']) 
                    {
                        $outNum = bcdiv($lval['total'], $lval['total_day'], 6);
                        $outNum = bccomp($lval['wait_num'], $outNum, 6)>0 ? $outNum : $lval['wait_num'];
                        
                        $lup = [];
                        $wait_day = $lval['wait_day']-1;
                        if ($wait_day<=0) {
                            $lup['status'] = 1;
                        }
                        $lup['wait_day'] = $wait_day;
                        $lup['wait_num'] = bcsub($lval['wait_num'], $outNum, 6);
                        $lup['over_num'] = bcadd($lval['over_num'], $outNum, 6);
                        UserLockOrder::query()->where('id', $lval['id'])->update($lup);
                        
                        if (bccomp($outNum, '0', 6)>0)
                        {
                            $destroyNum = bcmul($outNum, $destroy_rate, 6);
                            $marketNum = bcsub($outNum, $destroyNum, 6);
                            
                            $destroyPool = bcadd($destroyPool, $destroyNum, 6);
                            $marketPool = bcadd($marketPool, $marketNum, 6);
                            
                            if ($this->userList[$lval['user_id']]['parent_id']>0 && bccomp($zhi_lock_rate, '0', 6)>0) 
                            {
                                $zhiNum = bcmul($outNum, $zhi_lock_rate, 6);
                                if (bccomp($zhiNum, '0', 6)>0)
                                {
                                    $pDestroyNum = bcmul($zhiNum, $destroy_rate, 6);
                                    $pMarketNum = bcsub($zhiNum, $pDestroyNum, 6);
                                    
                                    $destroyPool = bcadd($destroyPool, $pDestroyNum, 6);
                                    $marketPool = bcadd($marketPool, $pMarketNum, 6);
                                }
                            }
                        }
                    }
                }
                
                //类型1销毁池2营销池
                PoolConfig::query()->where('type', 1)->increment('pool', $destroyPool);
                PoolConfig::query()->where('type', 2)->increment('pool', $marketPool);
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
                'parent_id' => 0,
                'yday_sign' => false
            ];
        }
    }
}
