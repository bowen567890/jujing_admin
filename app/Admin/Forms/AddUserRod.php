<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\FishRod;
use App\Models\FishRodOrder;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\RankRodLog;
use App\Models\UserFishRod;


class AddUserRod extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    public $rodLv = [1 => '一级', 2 => '二级', 3 => '三级',4 => '四级',5 => '五级',6 => '六级'];
    
    public function handle(array $input)
    {
        $in = $input;
        
        $rod_lv = $in['rod_lv'] ?? 1;
        
        $lockKey = 'AddUserRod';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 600);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        if (!isset($in['wallet']) || !$in['wallet']) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('请输入钱包地址');
        }
        
        if (!isset($in['num']) || intval($in['num'])<=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('请输入数量');
        }
        $num = intval($in['num']);
        
        $wallet = strtolower($in['wallet']);
        
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        $info = FishRod::query()->where(['cate'=>1, 'lv'=>$rod_lv])->first();
        if (!$info) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('鱼竿不存在');
        }

        for ($i=0; $i<$num; $i++) 
        {
            $in['pay_type'] = 1;
            $total = $info->price;
            
            $ordernum = get_ordernum();
            
            $ma_usdt_price = @bcadd(config('ma_usdt_price'), '0', 6);
            
            $FishRodOrder = new FishRodOrder();
            $FishRodOrder->ordernum = $ordernum;
            $FishRodOrder->user_id = $user->id;
            $FishRodOrder->fish_rod_id = $info->id;
            $FishRodOrder->price = $info->price;
            $FishRodOrder->blue = $info->blue;
            $FishRodOrder->red = $info->red;
            $FishRodOrder->image = $info->image;
            $FishRodOrder->lv = $info->lv;
            $FishRodOrder->total = $total;
            $FishRodOrder->pay_type = $in['pay_type'];
            $FishRodOrder->ma_usdt_price = $ma_usdt_price;
            $FishRodOrder->save();
            
            $OrderLog = new OrderLog();
            $OrderLog->ordernum = $ordernum;
            $OrderLog->user_id = $user->id;
            $OrderLog->type = 2;    //订单类型1提币2购买鱼竿3购买金币4购买银币
            $OrderLog->save();
            
            $data = [
                'remarks' => $ordernum,
                'amount' => $total,
            ];
            $this->buyFishRod($data);
        }
        
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
        $this->text('wallet','钱包地址')->required();
        $this->select('rod_lv','鱼竿等级')->options($this->rodLv)->default(1)->required();
        $this->number('num','数量')->min(1)->default(1)->required();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'rod_lv' => 1,
            'wallet' => '',
            'num' => 1,
        ];
    }
    
    
    private function buyFishRod($in)
    {
        $ordernum = $in['remarks'];
        $order = FishRodOrder::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        $user = $this->getUser($order->user_id);
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 6);
        
        $order->pay_status = 1;
        $order->hash = $hash;
        $order->save();
        
        $UserFishRod = new UserFishRod();
        $UserFishRod->ordernum = $order->ordernum;
        $UserFishRod->user_id = $order->user_id;
        $UserFishRod->lv = $order->lv;
        $UserFishRod->price = $order->price;
        $UserFishRod->blue = $order->blue;
        $UserFishRod->red = $order->red;
        $UserFishRod->image = $order->image;
        $UserFishRod->save();
        
        $userModel = new User();
        $userModel->handleAchievement($user->id, 1, 1, '_rod');
        $userModel->handlePerformance($user->path, 1, 1, '_rod');
        if ($order->pay_type==1)
        {
            $userModel->handleAchievement($user->id, $order->total, 1);
            $userModel->handlePerformance($user->path, $order->total, 1);
        } else {
            $userModel->handleAchievement($user->id, $order->total, 1, '_ma');
            $userModel->handlePerformance($user->path, $order->total, 1, '_ma');
        }
        
        $RankRodLog = new RankRodLog();
        $RankRodLog->user_id = $order->user_id;
        $RankRodLog->user_rod_id = $UserFishRod->id;
        $RankRodLog->num = 1;
        $RankRodLog->ordernum = $order->ordernum;
        $RankRodLog->save();
        
        //用户A推荐用户B，A，B都获得奖励。推荐人账户里没有鱼竿推荐用户无效，不获得奖励
        $direct_push_gold = intval(config('direct_push_gold'));
        $direct_push_diamond = intval(config('direct_push_diamond'));
        
        //个人奖励
        if ($direct_push_gold>0) {
            $cates = ['msg'=>'购买鱼竿', 'cate'=>5, 'ordernum'=>$order->ordernum,'from_user_id'=>$user->id,'content'=>'购买鱼竿奖励'];
            $userModel->handleUser('gold', $user->id, $direct_push_gold, 1, $cates);
        }
        if ($direct_push_diamond>0) {
            $cates = ['msg'=>'购买鱼竿', 'cate'=>5, 'ordernum'=>$order->ordernum,'from_user_id'=>$user->id,'content'=>'购买鱼竿奖励'];
            $userModel->handleUser('diamond', $user->id, $direct_push_diamond, 1, $cates);
        }
        
        //居间奖励
        if ($user->parent_id>0)
        {
            //直推竿数
            User::query()->where('id', $user->parent_id)->increment('zhi_rod', 1);
            
            $parent_id = $user->parent_id;
            $pAchievementRod = User::query()->where('id', $parent_id)->value('achievement_rod');
            if ($pAchievementRod>0)
            {
                if ($direct_push_gold>0) {
                    $cates = ['msg'=>'居间奖励', 'cate'=>4, 'ordernum'=>$order->ordernum,'from_user_id'=>$user->id,'content'=>'直推用户购买鱼竿奖励'];
                    $userModel->handleUser('gold', $parent_id, $direct_push_gold, 1, $cates);
                }
                if ($direct_push_diamond>0) {
                    $cates = ['msg'=>'居间奖励', 'cate'=>4, 'ordernum'=>$order->ordernum,'from_user_id'=>$user->id,'content'=>'直推用户购买鱼竿奖励'];
                    $userModel->handleUser('diamond', $parent_id, $direct_push_diamond, 1, $cates);
                }
            }
        }
        
        $this->setOrderStatus($ordernum, 1);
    }
    
    /**
     * 修改订单状态
     */
    protected function setOrderStatus($ordernum, $status=1) {
        OrderLog::query()->where('ordernum', $ordernum)->where('ordernum', $ordernum)->update(['status'=>$status]);
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
}
