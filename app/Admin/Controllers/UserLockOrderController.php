<?php

namespace App\Admin\Controllers;

use App\Models\UserLockOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Admin\Actions\Grid\AddLockOrder;

class UserLockOrderController extends AdminController
{
     public $statusArr = [
        0=>'释放中',
        1=>'已完结',
    ];
     public $sourceTypeArr = [
         1=>'注册',
         2=>'空投',
     ];
    protected function grid()
    {
        return Grid::make(UserLockOrder::with(['user']), function (Grid $grid) {
            
            $grid->tools(new AddLockOrder());
            
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('status')->using($this->statusArr)->label();
//             $grid->column('sign_date');
            $grid->column('total');
            $grid->column('wait_num');
            $grid->column('over_num');
            $grid->column('actual_num', '实际获得');
            $grid->column('total_day');
            $grid->column('wait_day');
            $grid->column('source_type')->using($this->sourceTypeArr)->label();
//             $grid->column('ordernum');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('id','desc');
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
                $filter->equal('user.wallet', '用户地址');
                $filter->equal('status')->select($this->statusArr);
                $filter->equal('source_type')->select($this->sourceTypeArr);
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new UserLockOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('status');
            $show->field('sign_date');
            $show->field('total');
            $show->field('wait_num');
            $show->field('over_num');
            $show->field('total_day');
            $show->field('wait_day');
            $show->field('source_type');
            $show->field('ordernum');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserLockOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('status');
            $form->text('sign_date');
            $form->text('total');
            $form->text('wait_num');
            $form->text('over_num');
            $form->text('total_day');
            $form->text('wait_day');
            $form->text('source_type');
            $form->text('ordernum');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
