<?php

namespace App\Admin\Controllers;

use App\Models\UserNftStat;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserNftStatController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserNftStat(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('lv');
            $grid->column('num');
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
        return Show::make($id, new UserNftStat(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('lv');
            $show->field('num');
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
        return Form::make(new UserNftStat(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('lv');
            $form->text('num');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
