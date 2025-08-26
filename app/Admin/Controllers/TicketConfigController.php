<?php

namespace App\Admin\Controllers;

use App\Models\TicketConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class TicketConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new TicketConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('ticket_price');
            $grid->column('insurance', '缴纳保险金');
//             $grid->column('status');
//             $grid->column('ticket_sale');
//             $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('ticket_price','asc');
            
            $grid->disableCreateButton();
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            $grid->disablePagination();	
        });
    }
  
    protected function form()
    {
        return Form::make(new TicketConfig(), function (Form $form) {
            $form->display('id');
            $form->number('ticket_price')->min(1)->required();
            $form->number('insurance', '缴纳保险金')->min(1)->required();
//             $form->text('status');
//             $form->text('ticket_sale');
        
            $form->saving(function (Form $form)
            {
//                 $id = $form->getKey();
//                 $static_rate = @bcadd($form->static_rate, '0', 2);
//                 if (bccomp($static_rate, '1', 2)>0 || bccomp('0', $static_rate, 2)>=0) {
//                     return $form->response()->error('静态收益比率不正确');
//                 }
                
//                 $form->static_rate = $static_rate;
            });
            
            $form->saved(function (Form $form, $result) {
                TicketConfig::SetListCache();
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
        return JsonResponse::make()->success('删除成功')->location('node_config');
    }
}
