<?php
namespace App\Console\Commands;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MyRedis;
use App\Models\MainCurrency;
use App\Models\RankConfig;
use App\Models\DepthConfig;
use App\Models\LuckyPool;
use App\Models\NodePool;
use App\Models\UserPower;
use App\Models\UserDogbee;
use App\Models\SeeConfig;
use App\Models\SyncPower;
use App\Models\SignOrder;
use App\Models\ManageRankConfig;
use App\Models\UserUsdt;
use App\Models\UserRankingMonth;
use App\Models\UserRankingDay;
use App\Models\TicketOrder;


class SyncTicketEvent extends Command
{
    // 自定义脚本命令签名
    protected $signature = 'command:SyncTicketEvent';

    // 自定义脚本命令描述
    protected $description = '入场券业绩';

    protected $userList = [];

    // 创建一个新的命令实例
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        $lockKey = 'command:SyncTicketEvent';
        $MyRedis = new MyRedis();
//                             $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 300);
        if ($lock)
        {
            $list = User::query()
                    ->join('ticket_order as t', 'users.id', '=', 't.user_id')
                    ->where('t.is_sync', '=', 0)
                    ->orderBy('t.id', 'asc')
                    ->get([
                        't.id','t.user_id','t.total_price','t.num','t.ordernum','t.is_sync',
                        'users.parent_id','users.level','users.path','t.created_at'
                    ])
                    ->toArray();
            if ($list)
            {
                $time = time();
                $date = date('Y-m-d H:i:s', $time);
                
                //等级配置
                $rankConf = RankConfig::GetListCache();
                $rankConf = $rankConf ? array_column($rankConf, null, 'lv') : [];
                
                //推荐配置
                $depthConf = DepthConfig::GetListCache();
                $depthConf = $depthConf ? array_column($depthConf, null, 'zhi_num') : [];
                $endDepth = 0;
                //获取最后层级
                if ($depthConf) {
                    $tmpDepth = end($depthConf);
                    $endDepth = $tmpDepth['depth'];
                    
                    $depthConf = array_reverse($depthConf);
                }
                
                $zhi_ticket_rate = @bcadd(config('zhi_ticket_rate'), '0', 3);
                $zhi_ticket_rate = $zhi_ticket_rate>='1' ? '1' : $zhi_ticket_rate;
                $zhi_ticket_rate = $zhi_ticket_rate<='0' ? '0' : $zhi_ticket_rate;
                
                $depth_ticket_rate = @bcadd(config('depth_ticket_rate'), '0', 3);
                $depth_ticket_rate = $depth_ticket_rate>='1' ? '1' : $depth_ticket_rate;
                $depth_ticket_rate = $depth_ticket_rate<='0' ? '0' : $depth_ticket_rate;
                
                
                $logIds = $usdtData = $userList = [];
                $userModel = new User();
                
                foreach ($list as $val) 
                {
                    $logIds[] = $val['id'];
                    
                    $datetime = $val['created_at'];
                    $timestamp = strtotime($datetime);
                    $month = date('Y-m', $timestamp);
                    $day = date('Y-m-d', $timestamp);
                    
                    //个人有效
                    $user = User::query()->where('id', $val['user_id'])->first(['id','level','is_valid','parent_id']);
                    if ($user->is_valid==0) 
                    {
                        $user->is_valid = 1;
                        $user->save();
                        //上级直推有效
                        if ($user->parent_id>0) {
                            User::query()->where('id', $user->parent_id)->increment('zhi_valid', 1);
                        }
                    }
                    
                    //个人业绩
                    $userModel->handleSelfYeji($val['user_id'], $val['total_price'], $val['num']);
                    
                    if ($user->parent_id>0 && $val['path']) 
                    {
                        //团队业绩
                        $userModel->handleTeamYeji($val['path'], $val['total_price'], $val['num']);
                        
                        //直推奖励
                        if (bccomp($zhi_ticket_rate, '0', 3)>0) 
                        {
                            $zhiNum = bcmul($val['total_price'], $zhi_ticket_rate, 6);
                            if (bccomp($zhiNum, '0', 6)>0)
                            {
                                $this->setUserList($user->parent_id);
                                $this->userList[$user->parent_id]['usdt'] = bcadd($this->userList[$user->parent_id]['usdt'], $zhiNum, 6);
                                
                                //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                                //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                                $usdtData[] = [
                                    'ordernum' => $val['ordernum'],
                                    'user_id' => $user->parent_id,
                                    'from_user_id' => $val['user_id'],
                                    'type' => 1,
                                    'cate' => 12,
                                    'total' => $zhiNum,
                                    'msg' => '直推奖励',
                                    'content' => "直推奖励",
                                    'depth' => 0,
                                    'created_at' => $datetime,
                                    'updated_at' => $datetime,
                                ];
                            }
                        }
                        
                        //上级信息
                        $parentIds = explode('-',trim($val['path'],'-'));
                        $parentIds = array_filter($parentIds);
                        
                        if ($parentIds) 
                        {
                            //层级奖励
                            if (bccomp($depth_ticket_rate, '0', 3)>0 && $depthConf)
                            {
                                $minLevel = $user->level-$endDepth;
                                $puserList = User::query()
                                    ->whereIn('id', $parentIds)
                                    ->where('level', '>=', $minLevel)
                                    ->orderBy('level', 'desc')
                                    ->get(['id','level','zhi_valid'])
                                    ->toArray();
                                if ($puserList)
                                {
                                    foreach ($puserList as $puser)
                                    {
                                        $pdepth = 0;
                                        foreach ($depthConf as $conf)
                                        {
                                            if ($puser['zhi_valid']>=$conf['zhi_num']) {
                                                $pdepth = $conf['depth'];
                                                break;
                                            }
                                        }
                                        
                                        //层级奖励
                                        $diffLevel = $user->level-$puser['level'];
                                        if ($pdepth>=$diffLevel)
                                        {
                                            $depthNum = bcmul($val['total_price'], $depth_ticket_rate, 6);
                                            if (bccomp($depthNum, '0', 6)>0)
                                            {
                                                $this->setUserList($puser['id']);
                                                $this->userList[$puser['id']]['usdt'] = bcadd($this->userList[$puser['id']]['usdt'], $depthNum, 6);
                                                
                                                //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
                                                //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
                                                $usdtData[] = [
                                                    'ordernum' => $val['ordernum'],
                                                    'user_id' => $puser['id'],
                                                    'from_user_id' => $val['user_id'],
                                                    'type' => 1,
                                                    'cate' => 13,
                                                    'total' => $depthNum,
                                                    'msg' => '层级奖励',
                                                    'content' => "层级{$diffLevel}层奖励",
                                                    'depth' => $diffLevel,
                                                    'created_at' => $datetime,
                                                    'updated_at' => $datetime,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        //计算小区业绩
                        if ($parentIds)
                        {
                            $puserList = User::query()
                                ->whereIn('id', $parentIds)
                                ->orderBy('level', 'desc')
                                ->get(['id','parent_id','level','zhi_num','small_yeji','team_yeji', 'team_num', 'small_num'])
                                ->toArray();
                            if ($puserList)
                            {
                                foreach ($puserList as $puser)
                                {
                                    //小区新增金额
                                    $large_yeji = $small_yeji = '0.00';
                                    //小区质押数量
                                    if ($puser['zhi_num']>0)
                                    {
                                        $largeUser = User::query()
                                            ->where('parent_id', $puser['id'])
                                            ->orderBy('total_yeji', 'desc')
                                            ->first(['id','total_yeji']);
                                        $large_yeji = @bcadd($largeUser->total_yeji, '0', 2);
                                        if ($puser['zhi_num']>=2)
                                        {
                                            $small_yeji = User::query()
                                                ->where('parent_id', $puser['id'])
                                                ->where('id', '<>', $largeUser->id)
                                                ->sum('total_yeji');
                                            $small_yeji = @bcadd($small_yeji, '0', 2);
                                        }
                                    }
                                    
                                    $nowSmallYeji = bcsub($puser['team_yeji'], $large_yeji, 2);
                                    
                                    if ($nowSmallYeji!=$puser['small_yeji'] && $puser['small_yeji']<$nowSmallYeji)
                                    {
                                        $diffYeji = bcsub($nowSmallYeji, $puser['small_yeji'], 2);
                                        
                                        $isExists = UserRankingMonth::query()
                                            ->where('user_id', $puser['id'])
                                            ->where('month', $month)
                                            ->exists();
                                        if ($isExists)
                                        {
                                            UserRankingMonth::query()
                                                ->where('user_id', $puser['id'])
                                                ->where('month', $month)
                                                ->increment('total',$diffYeji);
                                        }else{
                                            UserRankingMonth::query()->insert([
                                                'user_id' => $puser['id'],
                                                'month' => $month,
                                                'total' => $diffYeji,
                                                'created_at' => $datetime,
                                                'updated_at' => $datetime
                                            ]);
                                        }
                                        
                                        $isExists = UserRankingDay::query()
                                            ->where('user_id', $puser['id'])
                                            ->where('day', $day)
                                            ->exists();
                                        if ($isExists)
                                        {
                                            UserRankingDay::query()
                                                ->where('user_id', $puser['id'])
                                                ->where('day', $day)
                                                ->increment('total',$diffYeji);
                                        }else{
                                            UserRankingDay::query()->insert([
                                                'user_id' => $puser['id'],
                                                'day' => $day,
                                                'total' => $diffYeji,
                                                'created_at' => $datetime,
                                                'updated_at' => $datetime
                                            ]);
                                        }
                                    }
                                    
                                    
                                    //小区新增单数
                                    $large_num = $small_num = '0';
                                    //小区质押数量
                                    if ($puser['zhi_num']>0)
                                    {
                                        $largeUser = User::query()
                                            ->where('parent_id', $puser['id'])
                                            ->orderBy('total_num', 'desc')
                                            ->first(['id','total_num']);
                                        $large_num = @bcadd($largeUser->total_num, '0', 0);
                                        if ($puser['zhi_num']>=2)
                                        {
                                            $small_num = User::query()
                                                ->where('parent_id', $puser['id'])
                                                ->where('id', '<>', $largeUser->id)
                                                ->sum('total_num');
                                            $small_num = @bcadd($small_num, '0', 0);
                                        }
                                    }
                                    
                                    $nowSmallNum = bcsub($puser['team_num'], $large_num, 0);
                                    
                                    if ($nowSmallNum!=$puser['small_num'] && $puser['small_num']<$nowSmallNum)
                                    {
                                        $diffNum = bcsub($nowSmallNum, $puser['small_num'], 0);
                                        
                                        $isExists = UserRankingMonth::query()
                                            ->where('user_id', $puser['id'])
                                            ->where('month', $month)
                                            ->exists();
                                        if ($isExists)
                                        {
                                            UserRankingMonth::query()
                                                ->where('user_id', $puser['id'])
                                                ->where('month', $month)
                                                ->increment('num',$diffNum);
                                        }else{
                                            UserRankingMonth::query()->insert([
                                                'user_id' => $puser['id'],
                                                'month' => $month,
                                                'num' => $diffNum,
                                                'created_at' => $datetime,
                                                'updated_at' => $datetime
                                            ]);
                                        }
                                        
                                        $isExists = UserRankingDay::query()
                                            ->where('user_id', $puser['id'])
                                            ->where('day', $day)
                                            ->exists();
                                        if ($isExists)
                                        {
                                            UserRankingDay::query()
                                                ->where('user_id', $puser['id'])
                                                ->where('day', $day)
                                                ->increment('num',$diffNum);
                                        }else{
                                            UserRankingDay::query()->insert([
                                                'user_id' => $puser['id'],
                                                'day' => $day,
                                                'num' => $diffNum,
                                                'created_at' => $datetime,
                                                'updated_at' => $datetime
                                            ]);
                                        }
                                    }
                                    
                                    User::query()->where('id', $puser['id'])->update([
                                        'small_yeji'=>$nowSmallYeji,
                                        'small_num'=>$nowSmallNum
                                    ]);
                                }
                            }
                        }
                        
                        //更新用户等级
                        $userModel->UpdateUserRank($val['path'], $rankConf);
                    }
                }
                
                if ($logIds) {
                    $logIds = array_chunk($logIds, 1000);
                    foreach ($logIds as $ids) {
                        TicketOrder::query()->whereIn('id', $ids)->update(['is_sync'=>1]);
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
