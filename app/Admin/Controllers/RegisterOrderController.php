<?php

namespace App\Admin\Controllers;

use App\Models\RegisterOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RegisterOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new RegisterOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('parent_id');
            $grid->column('price');
            $grid->column('bnb');
            $grid->column('pay_type');
            $grid->column('bnb_price');
            $grid->column('ordernum');
            $grid->column('finish_time');
            $grid->column('hash');
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
        return Show::make($id, new RegisterOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('parent_id');
            $show->field('price');
            $show->field('bnb');
            $show->field('pay_type');
            $show->field('bnb_price');
            $show->field('ordernum');
            $show->field('finish_time');
            $show->field('hash');
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
        return Form::make(new RegisterOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('parent_id');
            $form->text('price');
            $form->text('bnb');
            $form->text('pay_type');
            $form->text('bnb_price');
            $form->text('ordernum');
            $form->text('finish_time');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
