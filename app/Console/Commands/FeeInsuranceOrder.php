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

class FeeInsuranceOrder extends Command
{
    protected $signature = 'FeeInsuranceOrder';

    protected $description = '保证金挖矿产出';

    protected $userList = [];
    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'FeeInsuranceOrder';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 120);
        if ($lock)
        {
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            
            $every_income_rate = @bcadd(config('every_income_rate'), '0', 3);
            $every_income_rate = $every_income_rate<=0 ? '0' : $every_income_rate;
            
            $list = User::query()
                ->join('insurance_order as o', 'users.id', '=', 'o.user_id')
                ->where('o.status', 0)
                ->where('o.is_redeem', 0)
                ->where('o.next_time', '<=', $datetime)
                ->orderBy('o.next_time', 'asc')
                ->get([
                    'o.id','o.user_id','o.ticket_price','o.total_income','o.wait_income','o.over_income','o.ordernum','o.next_time',
                    'users.parent_id','users.path'
                ])
                ->toArray();
                
            if ($list && $every_income_rate>0) 
            {
                $every_income_hour = @bcadd(config('every_income_hour'), '0', 0);
                $every_income_hour = $every_income_hour<=0 ? '25' : $every_income_hour;
                
                //等级配置
                $rankConf = RankConfig::GetListCache();
                $rankConf = $rankConf ? array_column($rankConf, null, 'lv') : [];
                
                $equalArr = [];
                if ($rankConf) {
                    foreach ($rankConf as $cval) {
                        $equalArr[$cval['lv']]['flag'] = false;
                        $equalArr[$cval['lv']]['num'] = '0';
                    }
                }
               
                $PoolConfig = PoolConfig::query()
                    ->whereIn('type', [2,3,4,5])   //2精英池子,3核心池子,4创世池子,5排名池子
                    ->get(['id','type','rate'])
                    ->toArray();
                if ($PoolConfig){
                    $PoolConfig = array_column($PoolConfig, null, 'id');
                    foreach ($PoolConfig as &$pval) {
                        $pval['add'] = '0';
                    }
                }
                    
                $usdtData = [];
                
                foreach ($list as $val) 
                {
                    $datetime = $val['next_time'];
                    
                    $oup = [];
                    $num = bcmul($val['ticket_price'], $every_income_rate, 2);
                    if (bccomp($num, $val['wait_income'], 2)>=0) {
                        $oup['wait_income'] = 0;
                        $oup['status'] = 1;
                        $oup['next_time'] = '';
                        $num = $val['wait_income'];
                        $oup['over_income'] = bcadd($val['over_income'], $num, 2);
                    } else {
                        $next_time = strtotime($val['next_time']);
                        $next_time = date('Y-m-d H:i:s', $next_time+$every_income_hour*3600);
                        $oup['next_time'] = $next_time;
                        $oup['wait_income'] = bcsub($val['wait_income'], $num, 2);
                        $oup['over_income'] = bcadd($val['over_income'], $num, 2);
                    }
                    InsuranceOrder::query()->where('id', $val['id'])->update($oup);

                    $poolSub = '0';
                    //分配奖池
                    if ($PoolConfig) 
                    {
                        foreach ($PoolConfig as $key=>$pconf) 
                        {
                            $pool = bcmul($num, $pconf['rate'], 6);
                            $poolSub = bcadd($poolSub, $pool, 6);
                            $PoolConfig[$key]['add'] = bcadd($PoolConfig[$key]['add'], $pool, 6);
                        }
                    }
                    
                    if (bccomp($poolSub, $num, 6)>=0) {
                        $num = '0';
                    } else {
                        $num = bcsub($num, $poolSub, 6);
                    }
                    
                    if (bccomp($num, '0', 6)>0) 
                    {
                        $this->setUserList($val['user_id']);
                        $this->userList[$val['user_id']]['usdt'] = bcadd($this->userList[$val['user_id']]['usdt'], $num, 6);
                        
                        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                        $usdtData[] = [
                            'ordernum' => $val['ordernum'],
                            'user_id' => $val['user_id'],
                            'from_user_id' => 0,
                            'type' => 1,
                            'cate' => 14,
                            'total' => $num,
                            'msg' => '静态奖励',
                            'content' => '静态奖励',
                            'created_at' => $datetime,
                            'updated_at' => $datetime,
                        ];
                        
                        User::query()->where('id', $val['user_id'])->increment('total_income', $num);
                        
                        //等级收益
                        if ($rankConf && $val['path']) 
                        {
                            //上级信息
                            $parentIds = explode('-',trim($val['path'],'-'));
                            $parentIds = array_filter($parentIds);
                            
                            if ($parentIds)
                            {
                                $parentList = User::query()
                                    ->where('rank', '>', 0)
                                    ->whereIn('id', $parentIds)
                                    ->orderBy('level', 'desc')
                                    ->get(['id','rank','level'])
                                    ->toArray();
                                if ($parentList)
                                {
                                    $currentRank = 0;
                                    $currentRate = '0';
                                    $tmpEqualArr = $equalArr;
                                    
                                    foreach ($parentList as $puser)
                                    {
                                        if (isset($rankConf[$puser['rank']]))
                                        {
                                            $config = $rankConf[$puser['rank']];
                                            if ($puser['rank']>$currentRank)
                                            {
                                                if (bccomp($config['rate'], '0', 3)>0 && bccomp($config['rate'], $currentRate, 3)>0)
                                                {
                                                    $diffRate = bcsub($config['rate'], $currentRate, 3);
                                                    $currentRank = $puser['rank'];
                                                    $currentRate = $config['rate'];
                                                    
                                                    $tnum = bcmul($num, $diffRate, 6);
                                                    if (bccomp($tnum, '0', 6)>0)
                                                    {
                                                        $tmpEqualArr[$puser['rank']]['num'] = $tnum;
                                                        
                                                        $this->setUserList($puser['id']);
                                                        $this->userList[$puser['id']]['usdt'] = bcadd($this->userList[$puser['id']]['usdt'], $tnum, 6);
                                                        
                                                        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                                                        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                                                        $usdtData[] = [
                                                            'ordernum' => $val['ordernum'],
                                                            'user_id' => $puser['id'],
                                                            'from_user_id' => $val['user_id'],
                                                            'type' => 1,
                                                            'cate' => 15,
                                                            'total' => $tnum,
                                                            'msg' => '等级奖励',
                                                            'content' => "等级V{$puser['rank']}奖励",
                                                            'created_at' => $datetime,
                                                            'updated_at' => $datetime,
                                                        ];
                                                    }
                                                }
                                            }
                                            else if ($puser['rank']==$currentRank && isset($tmpEqualArr[$puser['rank']]) && !$tmpEqualArr[$puser['rank']]['flag'])
                                            {
                                                $tmpEqualArr[$puser['rank']]['flag'] = true;
                                                
                                                if (bccomp($config['equal_rate'], '0', 3)>0 && bccomp($tmpEqualArr[$puser['rank']]['num'], '0', 6)>0)
                                                {
                                                    $tnum = bcmul($tmpEqualArr[$puser['rank']]['num'], $config['equal_rate'], 6);
                                                    if (bccomp($tnum, '0', 6)>0)
                                                    {
                                                        $this->setUserList($puser['id']);
                                                        $this->userList[$puser['id']]['usdt'] = bcadd($this->userList[$puser['id']]['usdt'], $tnum, 6);
                                                        
                                                        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                                                        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                                                        $usdtData[] = [
                                                            'ordernum' => $val['ordernum'],
                                                            'user_id' => $puser['id'],
                                                            'from_user_id' => $val['user_id'],
                                                            'type' => 1,
                                                            'cate' => 15,
                                                            'total' => $tnum,
                                                            'msg' => '等级奖励',
                                                            'content' => "等级V{$puser['rank']}平级奖励",
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
                    }
                }
                
                if ($this->userList) {
                    foreach ($this->userList as $uval) {
                        User::query()->where('id', $uval['user_id'])->increment('usdt', $uval['usdt']);
                    }
                }
                
                if ($usdtData) {
                    $usdtData = array_chunk($usdtData, 1000);
                    foreach ($usdtData as $ndata) {
                        UserUsdt::query()->insert($ndata);
                    }
                }
                
                if ($PoolConfig) {
                    foreach ($PoolConfig as $pv) {
                        PoolConfig::query()->where('id', $pv)->increment('pool', $pv['add']);
                    }
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
