<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\UpdateParentLog;
use Illuminate\Support\Facades\DB;
use App\Models\MyRedis;
use App\Models\RankUplog;

class UpdateParent extends Form implements LazyRenderable
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
        $in = $input;
        
        if (!isset($in['new_parent_wallet']) || !$in['new_parent_wallet']) {
            return $this->response()->error('请输入新上级地址1');
        }
        $new_parent_wallet = trim($in['new_parent_wallet']);
        if (!checkBnbAddress($new_parent_wallet)) {
            return $this->response()->error('新上级地址错误');
        }
        $new_parent_wallet = strtolower($new_parent_wallet);
        
        $MyRedis = new MyRedis();
        $lockKey = 'UpdateParent';
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 120);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $newUser = User::query()->where('wallet', $new_parent_wallet)->first();
        if (!$newUser) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('新上级不存在');
        }
        $new_parent_id = $newUser->id;
        
        $user = User::query()->where('id', $id)->first();
        if ($new_parent_id==$user->parent_id) 
        {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('新上级ID不能与旧上级ID相同');
        }
        
        if ($new_parent_id==$user->id)
        {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('不能设置自己为上级');
        }
        
        if($user->path) {
            $mpath = $user->path."{$id}-";
        } else {
            $mpath = "-{$id}-";
        }
        //伞下用户ID
        $isChild = User::query()
            ->where('id', '=', $new_parent_id)
            ->where('path', 'like', "{$mpath}%")
            ->first();
        if ($isChild) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('不能更换到自己的下级');
        }
        
        //新上级
        $newPath = '';
        $newLevel = 1;
        $newPath = empty($newUser->path) ? '-'.$newUser->id.'-' : $newUser->path.$newUser->id.'-';
        $newLevel = $newUser->level+1;
        
        DB::beginTransaction();
        try
        {
            $group_num = $user->group_num+1;
            
            $path = $user->path;
            $total_performance = $user->total_performance;
            $total_performance_vip = $user->total_performance_vip;
            $total_performance_all = $user->total_performance_all;
            
            if ($path) 
            {
                $parentIds = explode('-',trim($path,'-'));
                $parentIds = array_reverse($parentIds);
                $parentIds = array_filter($parentIds);
                if ($parentIds) 
                {
                    //旧上级修改数据
                    $yup = [
                        'group_num'=>DB::raw("`group_num`-{$group_num}"),               //团队人数减掉
                        'performance'=>DB::raw("`performance`-{$total_performance}"),
                        'total_performance'=>DB::raw("`total_performance`-{$total_performance}"),
                        'performance_vip'=>DB::raw("`performance_vip`-{$total_performance_vip}"),
                        'total_performance_vip'=>DB::raw("`total_performance_vip`-{$total_performance_vip}"),
                        'performance_all'=>DB::raw("`performance_all`-{$total_performance_all}"),
                        'total_performance_all'=>DB::raw("`total_performance_all`-{$total_performance_all}"),
                    ];
                    User::query()->whereIn('id', $parentIds)->update($yup);
                }
            }
            //旧直推用户减掉 直推人数
            if ($user->parent_id>0) {
                $zup = [
                    'zhi_num'=>DB::raw("`zhi_num`-1")
                ];
                User::query()->where('id', $user->parent_id)->update($zup);
            }
            
            //新上级
            if ($newPath) 
            {
                $parentIds = explode('-',trim($newPath,'-'));
                $parentIds = array_reverse($parentIds);
                $parentIds = array_filter($parentIds);
                if ($parentIds) {
                    //业绩
                    $yup = [
                        'group_num'=>DB::raw("`group_num`+{$group_num}"),               //团队人数减掉
                        'performance'=>DB::raw("`performance`+{$total_performance}"),
                        'total_performance'=>DB::raw("`total_performance`+{$total_performance}"),
                        'performance_vip'=>DB::raw("`performance_vip`+{$total_performance_vip}"),
                        'total_performance_vip'=>DB::raw("`total_performance_vip`+{$total_performance_vip}"),
                        'performance_all'=>DB::raw("`performance_all`+{$total_performance_all}"),
                        'total_performance_all'=>DB::raw("`total_performance_all`+{$total_performance_all}"),
                        ];
                    User::query()->whereIn('id', $parentIds)->update($yup);
                }
            }
            if ($new_parent_id>0) {
                $zup = [
                    'zhi_num'=>DB::raw("`zhi_num`+1")
                ];
                User::query()->where('id', $new_parent_id)->update($zup);
            }
            
            $old_parent_id = $user->parent_id;
            
            $UpdateParentLog = new UpdateParentLog();
            $UpdateParentLog->user_id = $user->id;
            $UpdateParentLog->old_parent_id = $user->parent_id;
            $UpdateParentLog->new_parent_id = $new_parent_id;
            $UpdateParentLog->group_num = $user->group_num;
            $UpdateParentLog->old_path = $user->path;
            $UpdateParentLog->new_path = $newPath;
            $UpdateParentLog->save();
            
            $oldLevel = $user->level;
            
            $user->parent_id =  $new_parent_id;
            $user->path =  $newPath;
            $user->level =  $newLevel;
            $user->save();
            
            $flag = 1;  //1加2减
            $diffLevel = 0;
            if ($newLevel>=$oldLevel) {
                $flag = 1;
                $diffLevel = $newLevel-$oldLevel;
            } else {
                $flag = 2;
                $diffLevel = $oldLevel-$newLevel;
            }
            
            $newPathArr[$user->id] = empty($newPath) ? '-'.$user->id.'-' : $newPath.$user->id.'-';
            //伞下用户ID
            $childList = User::query()
                ->where('path', 'like', "%-{$user->id}-%")
                ->orderBy('level', 'asc')
                ->get(['id','parent_id','level'])
                ->toArray();
            if ($childList) 
            {
                foreach ($childList as $cuser) 
                {
                    if ($flag==1) {
                        $up['level'] = DB::raw("`level`+{$diffLevel}");
                    } else {
                        $up['level'] = DB::raw("`level`-{$diffLevel}");
                    }
                    
                    if (!isset($newPathArr[$cuser['parent_id']])) 
                    {
                        if ($cuser['parent_id']>0) {
                            $ppuser = User::query()->where('id', $cuser['parent_id'])->first(['id','path']);
                            $newPathArr[$cuser['parent_id']] = $ppuser->path ? $ppuser->path.$ppuser->id.'-' : '-'.$ppuser->id.'-';
                            $up['path'] = $newPathArr[$cuser['parent_id']];
                        } else {
                            $up['path'] = '';
                        }
                    } 
                    else 
                    {
                        $up['path'] = $newPathArr[$cuser['parent_id']];
                    }
                    User::query()->where('id', $cuser['id'])->update($up);
                    if (!isset($newPathArr[$cuser['id']])) {
                        $newPathArr[$cuser['id']] = empty($up['path']) ? '-'.$cuser['id'].'-' : $up['path'].$cuser['id'].'-';
                    }
                }
            }
            
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            return $this->response()->error($e->getMessage().$e->getLine());
            return $this->response()->error('操作失败');
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
        $this->display('user_id', '当前用户ID');
        $this->display('user_wallet', '当前用户地址');
        $this->display('old_parent_id', '旧上级ID');
        $this->display('old_parent_wallet', '旧上级地址');
//         $this->text('new_parent_id', '新上级ID')->placeholder('填写新上级ID')->required();
        $this->text('new_parent_wallet', '新上级地址')->placeholder('填写新上级地址')->required();
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
        
        $old_parent_id = 0;
        $old_parent_wallet = '';
        $user = User::query()->where('id', $id)->first(['id','wallet','parent_id']);
        if ($user->parent_id>0) {
            $parentUser = User::query()->where('id', $user->parent_id)->first(['id','wallet','parent_id']);
            $old_parent_id = $parentUser->id;
            $old_parent_wallet = $parentUser->wallet;
        }
        
        return [
            'user_id' => $user->id,
            'user_wallet' => $user->wallet,
            'old_parent_id' =>$old_parent_id,
            'old_parent_wallet' => $old_parent_wallet,
            'new_parent_wallet' => '',
        ];
    }
}
