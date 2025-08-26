<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use App\Models\PredictionStage;
use App\Models\StagePriceLog;
use Illuminate\Support\Facades\DB;

class SetLastPrice extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $new_price = $input['new_price'];
        
        $lockKey = 'SetLastPrice';
        $MyRedis = new MyRedis();
        $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $stage = PredictionStage::query()->where('id',$id)->first();
        if (!$stage) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('数据不存在');
        }
        
        if ($stage->yc_status!=2) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('未预测结束价格，不能修改');
        }
        if ($stage->status!=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('已开奖，不能修改');
        }
        
        $new_price = @bcadd($new_price, '0' , 6);
        if (bccomp('0', $new_price, 6)>=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('价格不正确');
        }
        
        $time = time();
        $kjTime = strtotime($stage->kj_time);
        if ($time>$kjTime) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('开奖中，不能修改');
        }
        if (($time+60)>$kjTime) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('距离开奖时间太近，不能修改');
        }
        
        $StagePriceLog = new StagePriceLog();
        $StagePriceLog->stage_id = $stage->id;
        $StagePriceLog->old_price = $stage->yc_eprice;
        $StagePriceLog->new_price = $new_price;
        $StagePriceLog->save();
        
        $stage->yc_eprice = $new_price;
        $stage->save();
        
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
        $this->display('kj_time','开奖时间');
        $this->display('old_price','原价格');
        $this->decimal('new_price','新价格')->required();
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
        $old_price = 0;
        $kj_time = '';
        $stage = PredictionStage::query()->where('id', $id)->first(['kj_time','yc_eprice']);
        if ($stage) {
            $old_price = $stage->yc_eprice;
            $kj_time = $stage->kj_time;
        }
        return [
            'kj_time' => $kj_time,
            'old_price' => $old_price,
            'new_price' => 0
        ];
    }
}
