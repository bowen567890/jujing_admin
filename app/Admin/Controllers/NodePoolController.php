<?php

namespace App\Admin\Controllers;

use App\Models\NodePool;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class NodePoolController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new NodePool(), function (Grid $grid) {
            $grid->column('pool');
            $grid->column('w_rate')->help('购买算力金额,按此比率进入普通节点池(0.1=10%)');
            $grid->column('give_rate')->help('每小时按此比率拿出池子余额给所有普通节点均分(0.1=10%)');
            
            $grid->column('super_pool');
            $grid->column('super_w_rate')->help('购买算力金额,按此比率进入超级节点池(0.1=10%)');
            $grid->column('super_give_rate')->help('每天早上8:00按此比率拿出池子余额给所有超级节点均分(0.1=10%)');
            
            $grid->column('updated_at')->sortable();
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    protected function form()
    {
        return Form::make(new NodePool(), function (Form $form) {
            $form->display('pool');
            $form->decimal('w_rate')->required()->help('购买算力金额,按此比率进入普通节点池(0.1=10%)');
            $form->decimal('give_rate')->required()->help('每小时按此比率拿出池子余额给所有普通节点均分(0.1=10%)');
            
            $form->display('super_pool');
            $form->decimal('super_w_rate')->required()->help('购买算力金额,按此比率进入超级节点池(0.1=10%)');
            $form->decimal('super_give_rate')->required()->help('每天早上8:00按此比率拿出池子余额给所有超级节点均分(0.1=10%)');
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $w_rate = @bcadd($form->w_rate, '0', 4);
                if (bccomp($w_rate, '1', 4)>0 || bccomp('0', $w_rate, 4)>0) {
                    return $form->response()->error('普通节点入金入池比率不正确');
                }
                $give_rate = @bcadd($form->give_rate, '0', 4);
                if (bccomp($give_rate, '1', 4)>0 || bccomp('0', $give_rate, 4)>0) {
                    return $form->response()->error('普通节点分发比率不正确');
                }
                
                $super_w_rate = @bcadd($form->super_w_rate, '0', 4);
                if (bccomp($super_w_rate, '1', 4)>0 || bccomp('0', $super_w_rate, 4)>0) {
                    return $form->response()->error('超级节点入金入池比率不正确');
                }
                $super_give_rate = @bcadd($form->super_give_rate, '0', 4);
                if (bccomp($super_give_rate, '1', 4)>0 || bccomp('0', $super_give_rate, 4)>0) {
                    return $form->response()->error('超级节点分发比率不正确');
                }
                
                $form->w_rate = $w_rate;
                $form->give_rate = $give_rate;
                $form->super_w_rate = $super_w_rate;
                $form->super_give_rate = $super_give_rate;
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
        return JsonResponse::make()->success('删除成功')->location('node_pool');
    }
}
