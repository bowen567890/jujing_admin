<?php

namespace App\Admin\Controllers;

use App\Models\RankConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;
use App\Models\User;

class RankConfigController extends AdminController
{
    public $rankArr = [];
    public function __construct() {
        $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->rankArr = array_merge([0=>'V0'], $rankArr);
    }
    
    protected function grid()
    {
        return Grid::make(new RankConfig(), function (Grid $grid) {
//             $grid->column('id')->sortable();
            $grid->column('lv')->display(function() {
                return "<span class='label' style='background:#21b978'>V{$this->lv}</span>";
            });
            $grid->column('rate');
            $grid->column('equal_rate');
            $grid->column('small_num');
//             $grid->column('created_at');
//             $grid->column('updated_at');
            
            $grid->model()->orderBy('lv','asc');
            
            $grid->disableCreateButton();
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            $grid->disablePagination();	
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new RankConfig(), function (Form $form) {
//             $form->display('id');
            $form->display('name');
//             $form->number('lv')->default(1)->min(1)->required()->help('输入数字几就是V几(例输入1=V1)');
            
            $form->decimal('rate')->placeholder('0.1=10%')->help('奖励比率(0.1=10%)')->required();
            $form->decimal('equal_rate')->placeholder('0.1=10%')->help('平级比率(0.1=10%)')->required();
            $form->number('small_num')->default(0)->min(0)->required();
        
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $rate = @bcadd($form->rate, '0', 3);
                if (bccomp($rate, '1', 3)>0 || bccomp('0', $rate, 3)>0) {
                    return $form->response()->error('奖励比率不正确');
                }
                
                $equal_rate = @bcadd($form->equal_rate, '0', 3);
                if (bccomp($equal_rate, '1', 3)>0 || bccomp('0', $equal_rate, 3)>0) {
                    return $form->response()->error('平级比率不正确');
                }
                
//                 $lv = intval($form->lv);
//                 if ($form->isCreating()) {
//                     // 也可以这样获取自增ID
//                     $res = RankConfig::query()->where('lv', $lv)->first();
//                     if ($res) {
//                         return $form->response()->error('等级已存在');
//                     }
//                 }
//                 if ($form->isEditing()) {
//                     $res = RankConfig::query()->where('lv', $lv)->first();
//                     if ($res) {
//                         if ($res->id!=$id){
//                             return $form->response()->error('等级已存在');
//                         }
//                     }
//                 }
                $form->rate = $rate;
                $form->equal_rate = $equal_rate;
            });
            
            $form->saved(function (Form $form, $result) {
                RankConfig::SetListCache();
            });
                
            $form->disableViewButton();
            $form->disableDeleteButton();
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
    
    /**
     * 删除
     */
    public function destroy($id)
    {
//         $res = RankConfig::query()->where('id', $id)->first();
//         $isExist = User::query()->where('rank', $res->lv)->exists();
//         if ($isExist) {
//             return JsonResponse::make()->error('删除失败,用户存在此等级')->location('rank_config');
//         }
//         $descRank = RankConfig::query()->orderBy('lv', 'desc')->first();
//         if ($descRank && $descRank->id!=$res->id) {
//             return JsonResponse::make()->error('只能从最高级开始删除')->location('rank_config');
//         }
        
//         RankConfig::query()->where('id', $id)->delete();
//         RankConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('rank_config');
    }
}
