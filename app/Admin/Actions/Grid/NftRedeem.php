<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Modal;

use App\Models\User;
use App\Models\MyRedis;
use App\Models\NftCardOrder;

class NftRedeem extends RowAction
{
    /**
     * @return string
     */
    protected $action;

    // 注意action的构造方法参数一定要给默认值
    public function __construct($title = null, $action = 1)
    {
        $this->title = '赎回卡牌';
        $this->action = $action;
    }

    public function handle(Request $request)
    {
        $id = $this->getKey();
        
        if (!isset($id) || !$id) {
            return $this->response()->error('参数错误');
        }
        
        $lockKey = 'command:NftRedeem';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $order = NftCardOrder::query()->where('id', $id)->first();
        if (!$order) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('卡牌不存在');
        }
        
        if ($order->source_type!=1) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('当前卡牌不可赎回');
        }
        
        if ($order->status!=1) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('卡牌状态异常');
        }
        
        $order->status = 2;
        $order->redeem_time = date('Y-m-d H:i:s');
        $order->save();
        
        $MyRedis->del_lock($lockKey);
        
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确认赎回?', '赎回NFT卡牌'];
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [
            'action' => $this->action,
        ];
    }

}
