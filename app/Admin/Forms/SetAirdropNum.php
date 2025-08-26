<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\AirdropConfig;
use App\Models\AirdropRecord;

class SetAirdropNum extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $put_total = $input['put_total'] ?? 0;
        $optype = $input['optype'] == 2 ? 2 : 1;
        
        $lockKey = 'SetAirdropNum';
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $config = AirdropConfig::query()->where('id', 1)->first();
        
        $put_total  = bcadd($put_total, '0', 2);
        if (bccomp($put_total, '0', 2)<=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('数量不正确');
        }
        
        if (bccomp($put_total, '0', 2)>0)
        {
            if ($optype==2) {
                $cate = 2;
                if (bccomp($put_total, $config->put_total, 2)>0) {
                    $MyRedis->del_lock($lockKey);
                    return $this->response()->error("扣除数量大于现有数量");
                }
                AirdropConfig::query()->where('id', 1)->decrement('put_total', $put_total);
            } else {
                $cate = 1;
                AirdropConfig::query()->where('id', 1)->increment('put_total', $put_total);
            }
            
            $AirdropRecord = new AirdropRecord();
            $AirdropRecord->type = $optype;
            $AirdropRecord->total = $put_total;
            $AirdropRecord->cate = $cate;
            $AirdropRecord->save();
            
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
        $this->radio('optype','操作类型')->options([1=>'增加',2=>'减少'])->required();
        $this->decimal('put_total', '操作数量')->required();
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
            'put_total' => 0,
            'optype' => 1,
        ];
    }
}
