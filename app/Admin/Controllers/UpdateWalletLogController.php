<?php

namespace App\Admin\Controllers;

use App\Models\UpdateWalletLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UpdateWalletLogController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UpdateWalletLog(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('new_wallet');
            $grid->column('old_wallet');
            $grid->column('type');
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
        return Show::make($id, new UpdateWalletLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('new_wallet');
            $show->field('old_wallet');
            $show->field('type');
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
        return Form::make(new UpdateWalletLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('new_wallet');
            $form->text('old_wallet');
            $form->text('type');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
