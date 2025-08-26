<?php

namespace App\Admin\Controllers;

use App\Models\UserTicket;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserTicketController extends AdminController
{
    public $statusArr = [0=> '待使用', 1=>'已使用',2=>'已赠送'];
    public $sourceTypeArr = [1=>'平台购买',2=>'平台赠送',3=>'用户赠送'];
    protected function grid()
    {
        return Grid::make(UserTicket::with(['ticket', 'user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('ticket_id');
            $grid->column('ticket_id')->display(function() {
                if ($this->ticket){
                    return $this->ticket->ticket_price;
                }else{
                    return '0';
                }
            });
            $grid->column('status')->using($this->statusArr)->label('success');
            $grid->column('source_type')->using($this->sourceTypeArr)->label('success');
//             $grid->column('is_sync');
            $grid->column('created_at');
            
            $grid->model()->orderBy('id','desc');
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
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
        return Show::make($id, new UserTicket(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('ticket_id');
            $show->field('status');
            $show->field('source_type');
            $show->field('is_sync');
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
        return Form::make(new UserTicket(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('ticket_id');
            $form->text('status');
            $form->text('source_type');
            $form->text('is_sync');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
