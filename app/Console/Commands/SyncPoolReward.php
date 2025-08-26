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
use App\Models\PoolConfig;
use App\Models\UserRankingDay;

class SyncPoolReward extends Command
{
    protected $signature = 'SyncPoolReward';

    protected $description = '池子奖励分发';
    
    protected $userList = [];
    protected $usdtData = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'SyncPoolReward';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 1800);
        if ($lock)
        {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            
            $PoolConfig = PoolConfig::query()
                ->whereIn('type', [2,3,4,5])   //2精英池子,3核心池子,4创世池子,5排名池子
                ->get(['id','type','pool'])
                ->toArray();
            if ($PoolConfig) 
            {
                foreach ($PoolConfig as $pool) 
                {
                    if (in_array($pool['type'], [2,3,4]) && bccomp($pool['pool'], '0', 6)>0) 
                    {
                        $this->nodeReward($pool);
                    } else if ($pool['type']==5) {
                        $this->rankingReward($pool);
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
            }
            
            $MyRedis = new MyRedis();
            $MyRedis->del_lock($lockKey);
        }
    }
    
    public function nodeReward($pool)
    {
        //节点等级1精英2核心3创世
        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
        $tmpArr = [
            2 => [
                'node_rank' => 1,
                'cate' => 16,
                'msg' => '精英分红',
            ],
            3 => [
                'node_rank' => 2,
                'cate' => 17,
                'msg' => '核心分红',
            ],
            4 => [
                'node_rank' => 3,
                'cate' => 18,
                'msg' => '创世分红',
            ],
        ];
        
        $node_rank = $tmpArr[$pool['type']]['node_rank'];
        $cate = $tmpArr[$pool['type']]['cate'];
        $msg = $tmpArr[$pool['type']]['msg'];
        
        $list = User::query()->where('node_rank', $node_rank)
            ->get(['id','node_rank'])
            ->toArray();
        if ($list) 
        {
            $datetime = date('Y-m-d H:i:s');
            $ordernum = get_ordernum();
            $num = count($list);
            $avg = bcdiv($pool['pool'], $num, 6);
            if (bccomp($avg, '0', 6)>0) 
            {
                foreach ($list as $val) 
                {
                    $this->setUserList($val['id']);
                    $this->userList[$val['id']]['usdt'] = bcadd($this->userList[$val['id']]['usdt'], $avg, 6);
                    
                    //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                    //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                    $this->usdtData[] = [
                        'ordernum' => $ordernum,
                        'user_id' => $val['id'],
                        'from_user_id' => 0,
                        'type' => 1,
                        'cate' => $cate,
                        'total' => $avg,
                        'msg' => $msg,
                        'content' => $msg,
                        'created_at' => $datetime,
                        'updated_at' => $datetime,
                    ];
                }
                
                $subPool = bcmul($avg, $num, 6);
                PoolConfig::query()->where('id', $pool['id'])->decrement('pool', $subPool);
            }
        }
    }
    
    public function rankingReward($pool)
    {
        $time = time();
        $datetime = date('Y-m-d H:i:s', $time);
        $yDayTime = $time-86400;
        $yDay = date('Y-m-d', $yDayTime);
        
        $day_ranking_limit = intval(config('day_ranking_limit'));
        if ($day_ranking_limit>0) 
        {
            $list = UserRankingDay::query()
                ->where('day', $yDay)
                ->where('num', '>', 0)
                ->orderBy('num', 'desc')
                ->orderBy('updated_at', 'asc')
                ->limit($day_ranking_limit)
                ->get(['id','user_id','day','num'])
                ->toArray();
            if ($list) 
            {
                $ordernum = get_ordernum();
                $totalNum = $subPool = '0';
                foreach ($list as $val) {
                    $totalNum = bcadd($totalNum, $val['num'], 0);
                }
                
                foreach ($list as $key=>$val) 
                {
                    $rate = bcdiv($val['num'], $totalNum, 6);
                    $avg = bcmul($pool['pool'], $rate, 6);
                 
                    $ranking = $key+1;
                    UserRankingDay::query()
                        ->where('id', $val['id'])
                        ->update([
                            'ranking' => $ranking,
                            'reward' => $avg
                        ]);
                        
                    if (bccomp($avg, '0', 6)>0) 
                    {
                        $subPool = bcadd($subPool, $avg, 6);
                      
                        $this->setUserList($val['id']);
                        $this->userList[$val['id']]['usdt'] = bcadd($this->userList[$val['id']]['usdt'], $avg, 6);
                        
                        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                        $this->usdtData[] = [
                            'ordernum' => $ordernum,
                            'user_id' => $val['user_id'],
                            'from_user_id' => 0,
                            'type' => 1,
                            'cate' => 19,
                            'total' => $avg,
                            'msg' => '排名分红',
                            'content' => "排名第{$ranking}名分红",
                            'created_at' => $datetime,
                            'updated_at' => $datetime,
                        ];
                    }
                }
                PoolConfig::query()->where('id', $pool['id'])->decrement('pool', $subPool);
            }
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
