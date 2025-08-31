<?php

namespace App\Admin\Controllers;

use App\Models\SignOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SignOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SignOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('date');
            $grid->column('price');
            $grid->column('juj');
            $grid->column('pay_type');
            $grid->column('ordernum');
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
        return Show::make($id, new SignOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('date');
            $show->field('price');
            $show->field('juj');
            $show->field('pay_type');
            $show->field('ordernum');
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
        return Form::make(new SignOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('date');
            $form->text('price');
            $form->text('juj');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
