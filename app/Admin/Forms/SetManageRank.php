<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\ManageRankConfig;
use App\Models\ManageOperateLog;

class SetManageRank extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $manage_rank = $input['manage_rank'] ?? 0;
        
        $lockKey = 'user:info:'.$id;
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $user = User::query()->where('id',$id)->first();
        
        if ($user && $user->manage_rank!=$manage_rank) 
        {
            $ManageOperateLog = new ManageOperateLog();
            $ManageOperateLog->user_id = 0;
            $ManageOperateLog->target_id = $id;
            $ManageOperateLog->old_rank = $user->manage_rank;
            $ManageOperateLog->new_rank = $manage_rank;
            $ManageOperateLog->is_backend = 1;  //后台操作
            $ManageOperateLog->save();
            
            $user->manage_rank = $manage_rank;
            $user->save();
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
        $manageRankArr = ManageRankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->select('manage_rank','管理级别')->options($manageRankArr)->required();
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
        $manage_rank = User::query()->where('id', $id)->value('manage_rank');
        return [
            'manage_rank' => $manage_rank
        ];
    }
}
