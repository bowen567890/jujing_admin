<?php

namespace App\Admin\Controllers;

use App\Models\TicketOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class TicketOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new TicketOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('ticket_id');
            $grid->column('ticket_price');
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
        return Show::make($id, new TicketOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('ticket_id');
            $show->field('ticket_price');
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
        return Form::make(new TicketOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('ticket_id');
            $form->text('ticket_price');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
