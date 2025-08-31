<?php

namespace App\Admin\Controllers;

use App\Models\UserJuj;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserJujController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserJuj(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('from_user_id');
            $grid->column('type');
            $grid->column('total');
            $grid->column('ordernum');
            $grid->column('msg');
            $grid->column('cate');
            $grid->column('depth');
            $grid->column('content');
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
        return Show::make($id, new UserJuj(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('from_user_id');
            $show->field('type');
            $show->field('total');
            $show->field('ordernum');
            $show->field('msg');
            $show->field('cate');
            $show->field('depth');
            $show->field('content');
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
        return Form::make(new UserJuj(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('from_user_id');
            $form->text('type');
            $form->text('total');
            $form->text('ordernum');
            $form->text('msg');
            $form->text('cate');
            $form->text('depth');
            $form->text('content');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
