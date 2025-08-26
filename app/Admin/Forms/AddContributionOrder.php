<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\Withdraw;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\OrderLog;
use App\Models\NftCard;
use App\Models\NftCardOrder;
use App\Models\NftCardBlock;
use App\Models\MainCurrency;
use App\Models\UserContributionOrder;
use App\Models\ContributionConfig;
use App\Models\FundPool;
use App\Models\UserContributionPackage;
use App\Models\UserEntt;
use App\Models\UserDino;

class AddContributionOrder extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        $in = $input;
        
        if (!isset($in['wallet']) || !$in['wallet'])  {
            return $this->response()->error('请输入钱包地址');
        }
        if (!isset($in['entt_num']) || !$in['entt_num'])  {
            return $this->response()->error('请输入ENTT数量');
        }
        if (!isset($in['dino_num']) || !$in['dino_num'])  {
            return $this->response()->error('请输入DINO数量');
        }
        $entt_num = @bcadd($in['entt_num'], '0', 6);
        $dino_num = @bcadd($in['dino_num'], '0', 6);
        
        if (bccomp($entt_num, '0', 6)<=0) {
            return $this->response()->error('请输入ENTT数量');
        }
        if (bccomp($dino_num, '0', 6)<=0) {
            return $this->response()->error('请输入DINO数量');
        }
        
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return $this->response()->error('钱包地址有误');
        }
        $wallet = strtolower($wallet);
        
        $lockKey = 'AddContributionOrder';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return $this->response()->error('网络延迟');
        }
        
        $user = User::where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        //用户激活状态
        $user->is_activate = 1;
        $user->save();
        
        //同步到钓鱼系统
        $diaoyuUser = [];
        if ($user->account) {
            $diaoyuUser = User::on('diaoyu')->where('account', $user->account)->first(['id','account','wallet']);
        } else if ($user->wallet) {
            $diaoyuUser = User::on('diaoyu')->where('wallet', $user->wallet)->first(['id','account','wallet']);
        }
        if ($diaoyuUser) {
            User::on('diaoyu')->where('id', $diaoyuUser->id)->update(['is_activate'=>1]);
        }
        
        $enttCurrency = MainCurrency::query()->where('id', 2)->first(['rate','contract_address']);
        $dinoCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
        $entt_usdt_price = $enttCurrency->rate;
        $dino_usdt_price = $dinoCurrency->rate;
        
        $entt_num = @bcadd($in['entt_num'], '0', 6);
        $dino_num = @bcadd($in['dino_num'], '0', 6);
        
        $enttTotal = bcmul($entt_num, $entt_usdt_price, 6);
        $dinoTotal = bcmul($dino_num, $dino_usdt_price, 6);
        $total = bcadd($enttTotal, $dinoTotal, 6);
        
        $date = date('Y-m-d H:i:s');
        $ordernum = get_ordernum();
        $order = new UserContributionOrder();
        $order->ordernum = $ordernum;
        $order->user_id = $user->id;
        $order->total = $total;
        $order->pay_type = 9;   //支付类型1USDT(链上),2ENTT(链上),4DINO+ENTT(链上)5ENTT(系统)6USDT(系统)7DINO(链上)8DINO(系统),9DINO+ENTT(系统)10USDT+ENTT(链上)11USDT+DINO(链上)
        $order->entt_num = $entt_num;
        $order->dino_num = $dino_num;
        $order->entt_price = $entt_usdt_price;
        $order->dino_price = $dino_usdt_price;
        $order->pay_status = 1;
        $order->source_type = 2;    //来源1链上2后台3同步
        $order->finish_time = $date; 
        $order->save();
        
        $data = $dinoData = $enttData = $userList = [];
        $ContributionConfig = ContributionConfig::query()->get(['id','day','rate'])->toArray();
        $count = count($ContributionConfig);
        
        $numArr = [
            [
                'num' => $order->entt_num,
                'coin_type' => 2,   //币种1USDT,2ENTT3,DINO
            ],
            [
                'num' => $order->dino_num,
                'coin_type' => 3,   //币种1USDT,2ENTT3,DINO
            ]
        ];
        
        //见点奖
        $see_point_rate = @bcadd(config('see_point_rate'), '0', 6);
        $see_point_rate = bccomp($see_point_rate, '1', 6)>=0 ? '1' : $see_point_rate;
        $see_point_rate = bccomp($see_point_rate, '0', 6)>=0 ? $see_point_rate : '0';
        $see_point_depth = intval(config('see_point_depth'));
        //释放发放见点奖励
        $is_grant_seepoint = intval(config('is_grant_seepoint'));
        
        $totalContributionEntt = $totalContributionDino = '0';
        
        $studio_entt = $nft_entt = $community_entt = '0';
        $studio_dino = $nft_dino = $community_dino = '0';
        
        //工作室基金
        $fishing_studio_rate = @bcadd(config('fishing_studio_rate'), '0', 6);
        $fishing_studio_rate = bccomp($fishing_studio_rate, '1', 6)>=0 ? '1' : $fishing_studio_rate;
        $fishing_studio_rate = bccomp($fishing_studio_rate, '0', 6)>=0 ? $fishing_studio_rate : '0';
        //NFT 分红
        $fishing_nft_rate = @bcadd(config('fishing_nft_rate'), '0', 6);
        $fishing_nft_rate = bccomp($fishing_nft_rate, '1', 6)>=0 ? '1' : $fishing_nft_rate;
        $fishing_nft_rate = bccomp($fishing_nft_rate, '0', 6)>=0 ? $fishing_nft_rate : '0';
        //社区建设基金
        $fishing_community_rate = @bcadd(config('fishing_community_rate'), '0', 6);
        $fishing_community_rate = bccomp($fishing_community_rate, '1', 6)>=0 ? '1' : $fishing_community_rate;
        $fishing_community_rate = bccomp($fishing_community_rate, '0', 6)>=0 ? $fishing_community_rate : '0';
        
        foreach ($numArr as $numData)
        {
            $avgNum = bcdiv($numData['num'], $count, 6);
            
            if (bccomp($avgNum, '0', 6)>0)
            {
                foreach ($ContributionConfig as $val)
                {
                    $totalRate = bcadd('1', $val['rate'], 3);
                    $total_income = bcmul($avgNum, $totalRate, 6);
                    if (bccomp($total_income, '0', 6)>0)
                    {
                        $day_income = bcdiv($total_income, $val['day'], 6);
                        if (bccomp($day_income, '0', 6)>0)
                        {
                            $total_income = bcmul($day_income, $val['day'], 6);
                            if ($numData['coin_type']==2) {
                                $totalContributionEntt = bcadd($totalContributionEntt, $total_income, 6);
                            } else {
                                $totalContributionDino = bcadd($totalContributionDino, $total_income, 6);
                            }
                            $data[] = [
                                'order_id' => $order->id,
                                'user_id' => $order->user_id,
                                'coin_type' => $numData['coin_type'],   //币种1USDT,2ENTT3,DINO
                                'seed_money' => $avgNum,
                                'rate' => $val['rate'],
                                'day' => $val['day'],
                                'residue_day' => $val['day'],
                                'total_income' => $total_income,
                                'residue_income' => $total_income,
                                'day_income' => $day_income,
                                'ordernum' => get_ordernum(),
                                'created_at' => $date,
                                'updated_at' => $date,
                            ];
                        }
                    }
                }
            }
            
            //见点奖励
            $jdEntt = '0';
            if (bccomp($see_point_rate, '0', 6)>0) {
                $jdEntt = bcmul($numData['num'], $see_point_rate, 6);
            }
            
            if ($is_grant_seepoint && $see_point_depth>0 && bccomp($jdEntt, '0', 6)>0 && $user->path)
            {
                $avgJdEntt = bcdiv($jdEntt, $see_point_depth, 6);
                if (bccomp($avgJdEntt, '0', 6)>0)
                {
                    $pUserIds = explode('-',trim($user->path,'-'));
                    $pUserIds = array_reverse($pUserIds);
                    $pUserIds = array_filter($pUserIds);
                    if ($pUserIds)
                    {
                        $myLevel = $user->level;
                        $diffLevel = $user->level-$see_point_depth;
                        $puserList = User::query()
                            ->whereIn('id', $pUserIds)
                            ->where('level', '>=', $diffLevel)
                            ->orderBy('level', 'desc')
                            ->get(['id','rank','zhi_num','level','is_activate'])
                            ->toArray();
                        if ($puserList)
                        {
                            $jsNum = '0';
                            foreach ($puserList as $puser)
                            {
                                $chaLevel = $myLevel-$puser['level'];
                                
                                //没激活的就不给奖励，然后就紧缩給上一级
                                if ($puser['is_activate']==0) {
                                    //紧缩給上一级
                                    $jsNum = bcadd($jsNum, $avgJdEntt, 6);
                                    continue;
                                }
                                
                                //20层见点设置一个限制推1,2拿5，3,4拿10,5及以上拿20(直推也是激活),没激活的就不给奖励，然后就紧缩給上一级
                                $zhiNum = User::query()
                                    ->where('parent_id', $puser['id'])
                                    ->where('is_activate', 1)
                                    ->count();
                                if ($zhiNum>=5) {
                                } else if ($zhiNum==3 || $zhiNum==4) {
                                    if ($chaLevel>10) {
                                        //紧缩給上一级
                                        $jsNum = bcadd($jsNum, $avgJdEntt, 6);
                                        continue;
                                    }
                                } else if ($zhiNum==1 || $zhiNum==2) {
                                    if ($chaLevel>5) {
                                        //紧缩給上一级
                                        $jsNum = bcadd($jsNum, $avgJdEntt, 6);
                                        continue;
                                    }
                                } else {
                                    //紧缩給上一级
                                    $jsNum = bcadd($jsNum, $avgJdEntt, 6);
                                    continue;
                                }
                                
                                if (!isset($userList[$puser['id']])) {
                                    $userList[$puser['id']] = [
                                        'user_id' => $puser['id'],
                                        'entt' => '0',
                                        'dino' => '0',
                                    ];
                                }
                                
                                //个人+紧缩
                                $sjNum = bcadd($avgJdEntt, $jsNum, 6);
                                $jsNum = '0';
                                
                                if ($numData['coin_type']==2)
                                {
                                    $userList[$puser['id']]['entt'] = bcadd($userList[$puser['id']]['entt'], $sjNum, 6);
                                    //分类1后台操作2余额提币3金币兑换4余额充值5幸运祈福6兑换金币7兑换钻石8见点奖励9钓鱼奖励10组队奖励
                                    $enttData[] = [
                                        'user_id' => $puser['id'],
                                        'from_user_id' => $user->id,
                                        'type' => 1,
                                        'total' => $sjNum,
                                        'ordernum' => $ordernum,
                                        'msg' => '见点奖励',
                                        'cate' => 8,
                                        'content' => "见点奖励",
                                        'created_at' => $date,
                                        'updated_at' => $date,
                                    ];
                                }
                                else
                                {
                                    $userList[$puser['id']]['dino'] = bcadd($userList[$puser['id']]['dino'], $sjNum, 6);
                                    //分类1后台操作2余额提币3金币兑换4余额充值5幸运祈福6兑换金币7兑换钻石8见点奖励9钓鱼奖励10组队奖励
                                    $dinoData[] = [
                                        'user_id' => $puser['id'],
                                        'from_user_id' => $user->id,
                                        'type' => 1,
                                        'total' => $sjNum,
                                        'ordernum' => $ordernum,
                                        'msg' => '见点奖励',
                                        'cate' => 9,
                                        'content' => "见点奖励",
                                        'created_at' => $date,
                                        'updated_at' => $date,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            //基金分配
            if ($numData['coin_type']==2)
            {
                $studio_entt = bcmul($numData['num'], $fishing_studio_rate, 6);
                $nft_entt = bcmul($numData['num'], $fishing_nft_rate, 6);
                $community_entt = bcmul($numData['num'], $fishing_community_rate, 6);
                
            }
            else
            {
                $studio_dino = bcmul($numData['num'], $fishing_studio_rate, 6);
                $nft_dino = bcmul($numData['num'], $fishing_nft_rate, 6);
                $community_dino = bcmul($numData['num'], $fishing_community_rate, 6);
            }
        }
        
        $poolUp = [
            'studio_entt' => DB::raw("`studio_entt`+{$studio_entt}"),
            'studio_dino' => DB::raw("`studio_dino`+{$studio_dino}"),
            'nft_entt' => DB::raw("`nft_entt`+{$nft_entt}"),
            'nft_dino' => DB::raw("`nft_dino`+{$nft_dino}"),
            'community_entt' => DB::raw("`community_entt`+{$community_entt}"),
            'community_dino' => DB::raw("`community_dino`+{$community_dino}"),
            ];
        FundPool::query()->where('id', 1)->update($poolUp);
        if ($data) {
            UserContributionPackage::query()->insert($data);
        }
        User::query()->where('id', $order->user_id)->update([
            'contribution_entt' => DB::raw("`contribution_entt`+{$totalContributionEntt}"),
            'contribution_dino' => DB::raw("`contribution_dino`+{$totalContributionDino}"),
        ]);
        
        if ($enttData) {
            UserEntt::query()->insert($enttData);
        }
        if ($dinoData) {
            UserDino::query()->insert($dinoData);
        }
        if ($userList) {
            foreach ($userList as $uvv)
            {
                User::query()->where('id', $uvv['user_id'])->update([
                    'entt'=>DB::raw("`entt`+{$uvv['entt']}"),
                    'dino'=>DB::raw("`dino`+{$uvv['dino']}")
                ]);
            }
        }
        
        $userModel = new User();
        //个人业绩
        $userModel->handleAchievement($user->id, $order->total);
        $userModel->handlePerformance($user->path, $order->total);
        
        $MyRedis->del_lock($lockKey);
        
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }
    
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('wallet','用户地址')->placeholder('用户钱包地址')->required();
        $this->decimal('entt_num', 'ENTT数量')->required();
        $this->decimal('dino_num', 'DINO数量')->required();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'wallet' => '',
            'entt_num' => 0,
            'dino_num' => 0,
        ];
    }
}
