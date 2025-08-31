<?php

namespace App\Admin\Controllers;

use App\Models\UserNft;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserNftController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserNft(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('target_id');
            $grid->column('source_type');
            $grid->column('lv');
            $grid->column('status');
            $grid->column('upgrade_type');
            $grid->column('sign_date');
            $grid->column('total_day');
            $grid->column('wait_day');
            $grid->column('over_day');
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
        return Show::make($id, new UserNft(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('target_id');
            $show->field('source_type');
            $show->field('lv');
            $show->field('status');
            $show->field('upgrade_type');
            $show->field('sign_date');
            $show->field('total_day');
            $show->field('wait_day');
            $show->field('over_day');
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
        return Form::make(new UserNft(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('target_id');
            $form->text('source_type');
            $form->text('lv');
            $form->text('status');
            $form->text('upgrade_type');
            $form->text('sign_date');
            $form->text('total_day');
            $form->text('wait_day');
            $form->text('over_day');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
