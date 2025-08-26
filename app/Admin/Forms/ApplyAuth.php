<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\OrderLog;
use Illuminate\Support\Facades\DB;
use App\Models\MyRedis;
use App\Models\UserRecharge;
use App\Models\AllocationApply;
use App\Models\FreezeLog;
use App\Models\RealAuth;
use App\Models\OperateLog;
use Dcat\Admin\Admin;
use App\Models\UserMessage;

class ApplyAuth extends Form implements LazyRenderable
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
        $id = $this->payload['id'] ?? 0;
        $status = $input['status'] == 1 ? 1 : 2;
        
        $lockKey = 'forms:ApplyAuth:'.$id;
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $apply = AllocationApply::query()->where('id', $id)->first();
        if (!$apply || $apply->status!=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('状态异常');
        }
        
        if ($status==1) 
        {
            $apply->status = 1;
            $apply->save();
            User::query()->where('id', $apply->user_id)->update([
                'is_allocation'=>1
            ]);
        } 
        else 
        {
            $apply->status = 2;
            $apply->save();
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
        $this->radio('status', '操作类型')
            ->options([
                1 => '通过',
                2 => '拒绝',
            ])
            ->default(1);
            
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
            'status' => 1
        ];
    }
}
