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

class AddOrder extends Form implements LazyRenderable
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
        if (!isset($in['card_id']) || !$in['card_id'])  {
            return $this->response()->error('请选择NFT卡牌');
        }
        $card_id = intval($in['card_id']);
        
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return $this->response()->error('钱包地址有误');
        }
        $wallet = strtolower($wallet);
        
        $lockKey = 'AddNftOrder';
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('网络延迟');
        }
        
        $user = User::where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('账号不存在');
        }
        
        $card = NftCard::query()->where('id', $card_id)->first();
        if (!$card) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('卡牌不存在');
        }
        
        //节点产币那里同一级别不能重复购买，购买了相应级别之后，该按钮变灰色点不动
        $oNum = NftCardOrder::query()
            ->where('user_id', $user->id)
            ->where('pay_status', 1)
            ->where('status', 1)    //状态0未支付1运行中2已赎回
            ->where('card_id', $card->id)
            ->count();
        if ($oNum>=1) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户已持有此卡牌');
        }
        
        
        $ordernum = get_ordernum();
        
        $CardOrder = new NftCardOrder();
        $CardOrder->ordernum = $ordernum;
        $CardOrder->user_id = $user->id;
        $CardOrder->card_id = $card->id;
        $CardOrder->lv = $card->lv;
        $CardOrder->price = $card->price;
        $CardOrder->image = $card->image;
        $CardOrder->pay_type = 2;
        $CardOrder->pay_status = 1;
        $CardOrder->pay_time = date('Y-m-d H:i:s');
        $CardOrder->block_day = $card->day;
        $CardOrder->block_num = $card->num;
        $CardOrder->source_type = 1;
        $CardOrder->status = 1;
        $CardOrder->save();
        
        //爆块
        $time = time();
        if ($CardOrder->block_num>0)
        {
            $NftCardBlock = new NftCardBlock();
            $NftCardBlock->user_id = $CardOrder->user_id;
            $NftCardBlock->order_id = $CardOrder->id;
            $NftCardBlock->lv = $CardOrder->lv;
            $NftCardBlock->num = $CardOrder->block_num;
            $NftCardBlock->end_time = date('Y-m-d H:i:s', $time+($CardOrder->block_day*86400));
            $NftCardBlock->save();
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
        $this->text('wallet','用户地址')->placeholder('用户钱包地址')->required();
        $this->select('card_id', 'NFT卡牌')
            ->options(NftCard::query()->pluck('name', 'id')->toArray())
            ->required();
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
            'card_id' => 0,
        ];
    }
}
