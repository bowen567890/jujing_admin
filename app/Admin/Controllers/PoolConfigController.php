<?php

namespace App\Admin\Controllers;

use App\Models\PoolConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class PoolConfigController extends AdminController
{
    public $typeArr = [1=> '提现池子', 2=>'精英池子',3=>'核心池子',4=>'创世池子',5=>'排名池子'];
    
    protected function grid()
    {
        return Grid::make(new PoolConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('type')->using($this->typeArr)->label('success');
            $grid->column('pool');
            $grid->column('rate')->help('0.1=10%');
//             $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
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
        return Form::make(new PoolConfig(), function (Form $form) {
//             $form->display('id');
            $form->display('type')->customFormat(function ($type) {
                $arr = [1=> '提现池子', 2=>'精英池子',3=>'核心池子',4=>'创世池子',5=>'排名池子'];
                return $arr[$type];
            });
            $form->display('pool');
            $form->decimal('rate')->required()->help('0.1=10%');
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                
                if ($id==1) {
                    $rate = '0';
                } 
                else 
                {
                    $rate = @bcadd($form->rate, '0', 3);
                    if (bccomp($rate, '1', 3)>0 || bccomp('0', $rate, 3)>0) {
                        return $form->response()->error('分配比率不正确');
                    }
                }
                
                $form->rate = $rate;
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
        return JsonResponse::make()->success('删除成功')->location('pool_config');
    }
}
