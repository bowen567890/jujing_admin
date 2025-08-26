<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\Box;
use App\Models\BoxRate;


class UpdateBoxRate extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    
    public function handle(array $input)
    {
        $in = $input;
        
        $box_id = $in['box_id'] ?? 0;

        $lockKey = 'UpdateBoxRate';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 600);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $box = Box::query()
            ->where('id', intval($box_id))
            ->first();
        
        if ($box) 
        {
            $date = date('Y-m-d H:i:s');
            
            $rate = @bcadd($box->rate, '0', 3);
            if (bccomp($rate, '0', 3)<=0) {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error('比率不正确');
            }
            $rate = bccomp($rate, '1', 3)>=0 ? 1 : $rate;
            
            $upData = [];
            
            $winNum  = $rate*1000;
            $notWinNum = 1000-$winNum;
            
            $winTmp = [];
            if ($winNum>0) {
                $tmp = [
                    'box_id' => $box->id,
                    'win' => 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
                $winTmp = array_pad($winTmp, $winNum, $tmp);
            }
            
            $notTmp = [];
            if ($notWinNum>0) {
                $tmp = [
                    'box_id' => $box->id,
                    'win' => 0,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
                $notTmp = array_pad($notTmp, $notWinNum, $tmp);
            }
            $tmpData = array_merge($winTmp, $notTmp);
            if ($tmpData) {
                shuffle($tmpData);
                BoxRate::query()->where('box_id', $box->id)->delete();
                BoxRate::query()->insert($tmpData);
            }
        } else {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('请选择盲盒');
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
        $typeArr = [1=>'普通盲盒', 2=>'活动盲盒'];
        $data = [];
        $list = Box::query()->get(['id','name','type'])->toArray();
        foreach ($list as $val) {
            $data[$val['id']] = $typeArr[$val['type']].' | '.$val['name'];
        }
        
        $this->select('box_id','盲盒')->options($data)
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
            'box_id' => 0
        ];
    }
}
