<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;

class SetBalanceNum extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    public $balanceType = [
        1=>'USDT'
    ];
    
    public $balanceArr = [
        1=>'usdt'
    ];
    
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $num = $input['num'] ?? 0;
        $optype = $input['optype'] == 2 ? 2 : 1;
        $type = $input['type'];
        
        $lockKey = 'user:info:'.$id;
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $user = User::query()->where('id',$id)->first();
        
        $balanceTxt  = $this->balanceType[$type];
        $balance  = $this->balanceArr[$type];
        
        if (intval($num)>0)
        {
            $cate = 1;
            $msg = '链上增加';
            if ($optype==2) {
                if (bccomp($num, $user->$balance, 0)>0) {
                    $MyRedis->del_lock($lockKey);
                    return $this->response()->error("扣除数量大于现有{$balanceTxt}数量");
                }
                $cate = 2;
                $msg = '链上扣除';
            }
            
            $ordernum = get_ordernum();
            $userModel = new User();
            $userModel->handleUser($balance, $user->id, $num, $optype, ['cate'=>$cate,'msg'=>$msg, 'ordernum'=>$ordernum]);
            
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
            'type' => 1,
        ];
    }
}
