<?php

namespace App\Admin\Controllers;

use App\Models\UserLockOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserLockOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserLockOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('status');
            $grid->column('sign_date');
            $grid->column('total');
            $grid->column('wait_num');
            $grid->column('over_num');
            $grid->column('total_day');
            $grid->column('wait_day');
            $grid->column('source_type');
            $grid->column('ordernum');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
