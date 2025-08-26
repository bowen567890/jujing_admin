<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\SitePool;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;

class SetPoolNum extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    public $balanceType = [
//         1=>'绿帽周奖池',
//         2=>'红帽周奖池',
//         3=>'使者池',
//         4=>'天使池',
//         5=>'宙斯池',
        6=>'空投池',
    ];
    
    public $balanceArr = [
//         1=>'green_pool',
//         2=>'red_pool',
//         3=>'envoy_pool',
//         4=>'angel_pool',
//         5=>'zeus_pool',
        6=>'airdrop_pool',
    ];
    
    public function handle(array $input)
    {
        $num = $input['num'] ?? 0;
        $optype = $input['optype'] == 2 ? 2 : 1;
        $type = $input['type'];
        
        $lockKey = 'SetPoolNum';
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $pool = SitePool::query()->where('id',1)->first();
        
        $balanceTxt  = $this->balanceType[$type];
        $balance  = $this->balanceArr[$type];
        
        if (intval($num)>0)
        {
            if ($optype==2) {
                if (bccomp($num, $pool->$balance, 0)) {
                    $MyRedis->del_lock($lockKey);
                    return $this->response()->error("扣除数量大于现有{$balanceTxt}数量");
                }
            }
            
            if ($optype==1) {
                SitePool::query()->where('id',1)->increment($balance, $num);
            } else {
                SitePool::query()->where('id',1)->decrement($balance, $num);
            }
            
        } else {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('操作数量需大于0');
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
        $this->radio('type','余额类型')->options($this->balanceType)->required();
        $this->radio('optype','操作类型')->options([1=>'增加',2=>'减少'])->required();
        $this->number('num', '数量')->min(0)->required();
        $this->disableResetButton();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $id = $this->payload['id'] ?? 0;
        
        return [
            'num' => 0,
            'optype' => 1,
            'type' => 6,
        ];
    }
}
