<?php

namespace App\Admin\Controllers;

use App\Models\TeamGasConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class TeamGasConfigController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new TeamGasConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('zhi_num');
            $grid->column('gas_rate');
//             $grid->column('created_at');
//             $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('zhi_num','asc');
            
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            $grid->disablePagination();	
        });
    }

    protected function form()
    {
        return Form::make(new TeamGasConfig(), function (Form $form) {
            $form->display('id');
            $form->number('zhi_num')->min(1)->default(1)->required();
            $form->decimal('gas_rate')->required()->help('返利团队Gas比率(0.1=10%)');
        
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
            });
                
            $form->saved(function (Form $form, $result) {
                TeamGasConfig::SetListCache();
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
        TeamGasConfig::query()->where('id', $id)->delete();
        TeamGasConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('team_gas_config');
    }
}
