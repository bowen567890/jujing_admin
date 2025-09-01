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
use App\Models\TicketConfig;
use App\Models\UserTicket;
use App\Models\NodeConfig;
use App\Models\NodeOrder;
use App\Models\RankConfig;
use App\Models\UserLockOrder;


class AddLockOrder extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    public function handle(array $input)
    {
        $in = $input;
        
        $total = $in['total'] ?? 30;
        
        $lockKey = 'AddLockOrder';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        if (!isset($in['wallet']) || !$in['wallet']) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('请输入钱包地址');
        }
        
        $wallet = strtolower($in['wallet']);
        
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        $lock_juj_day = intval(config('lock_juj_day'));
        $lock_juj_day = $lock_juj_day>0 ? $lock_juj_day : 30;
        
        $ordernum = get_ordernum();
        $UserLockOrder = new UserLockOrder();
        $UserLockOrder->ordernum = $ordernum;
        $UserLockOrder->user_id = $user->id;
        $UserLockOrder->total = $total;
        $UserLockOrder->wait_num = $total;
        $UserLockOrder->total_day = $lock_juj_day;
        $UserLockOrder->wait_day = $lock_juj_day;
        $UserLockOrder->source_type = 2;   //来源类型1注册赠送2空投赠送
        $UserLockOrder->save();
        
        User::query()->where('id', $user->id)->increment('juj_lock', $total);
        
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
        $this->number('total','空投数据')->default(30)->min(1)->required()->help('空投JUJ锁仓订单数量');
//         $this->number('num','数量')->min(1)->default(1)->required();
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
            'total' => '30'
        ];
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
}
