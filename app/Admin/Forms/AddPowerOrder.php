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
use App\Models\PowerOrder;
use App\Models\PowerConf;
use App\Models\UserPower;
use App\Models\UserPowerList;

class AddPowerOrder extends Form implements LazyRenderable
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
            return $this->response()->error('请填写用户地址');
        }
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return $this->response()->error('钱包地址有误');
        }
        $wallet = strtolower($wallet);
        
        if (!isset($in['conf_id']) || !$in['conf_id']) {
            return $this->response()->error('请选择服务器');
        }
        $conf_id = intval($in['conf_id']);
        
        $lockKey = 'AddPowerOrder';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return $this->response()->error('网络延迟');
        }
        
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        $PowerConf = PowerConf::GetListCache();
        if (!$PowerConf) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('服务器不存在');
        }
        $PowerConf = array_column($PowerConf, null, 'id');
        
        if (!isset($PowerConf[$conf_id])) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('服务器不存在');
        }
       
        //价格自定义
        $power_price = $PowerConf[$conf_id]['power_price'];
        $usdt_power_price = $PowerConf[$conf_id]['usdt_power_price'];
        $usdt = $PowerConf[$conf_id]['usdt'];
        $power = $PowerConf[$conf_id]['power'];
        $day = $PowerConf[$conf_id]['day'];
        
        $datetime = date('Y-m-d H:i:s');
        
        $ordernum = get_ordernum();
        $newOrder = new PowerOrder();
        $newOrder->ordernum = $ordernum;
        $newOrder->conf_id = $conf_id;
        $newOrder->user_id = $user->id;
        $newOrder->usdt = $usdt;
        $newOrder->power = $power;
        $newOrder->day = $day;
        $newOrder->power_price = $power_price;
        $newOrder->usdt_power_price = $usdt_power_price;
        $newOrder->save();
        
        $release_time = date('Y-m-d H:i:s', strtotime($datetime)+86400);
        
        $powerList[] = [
            'user_id' => $user->id,
            'power' => $power,
            'residue_power' => $power,
            'day' => $day,
            'residue_day' => $day,
            'source' => 1,  //来源1购买算力
            'release_time' => $release_time,
            'ordernum' => $ordernum,
            'created_at' => $datetime,
            'updated_at' => $datetime,
        ];
        
        //分类1后台操作2购买算力
        $powerData[] = [
            'ordernum' => $ordernum,
            'user_id' => $user->id,
            'from_user_id' => 0,
            'type' => 1,
            'cate' => 2,
            'total' => $power,
            'msg' => '购买算力',
            'content' => '购买算力',
            'created_at' => $datetime,
            'updated_at' => $datetime,
        ];
        
        
        User::query()->where('id', $user->id)->increment('power', $power);
        UserPower::query()->insert($powerData);
        UserPowerList::query()->insert($powerList);
        
        $userModel = new User();
        //个人业绩
        $userModel->handleAchievement($user->id, $usdt);
        $userModel->handlePerformance($user->path, $usdt);
        
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
        $list = [];
        $confList = PowerConf::GetListCache();
        foreach ($confList as $val) {
            $list[$val['id']] = $val['usdt'].'USDT | '.$val['power'].'算力';
        }
        
        $this->text('wallet','用户地址')->required();
        $this->select('conf_id','服务器')
            ->options($list)
            ->required()
            ->default(1);
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
            'conf_id' => 1,
        ];
    }
}
