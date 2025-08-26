<?php

namespace App\Admin\Controllers;

use App\Models\UpdateParentLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UpdateParentLogController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UpdateParentLog(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('old_parent_id');
            $grid->column('new_parent_id');
            $grid->column('group_num');
            $grid->column('old_path');
            $grid->column('new_path');
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
        return Show::make($id, new UpdateParentLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('old_parent_id');
            $show->field('new_parent_id');
            $show->field('group_num');
            $show->field('old_path');
            $show->field('new_path');
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
        return Form::make(new UpdateParentLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('old_parent_id');
            $form->text('new_parent_id');
            $form->text('group_num');
            $form->text('old_path');
            $form->text('new_path');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
