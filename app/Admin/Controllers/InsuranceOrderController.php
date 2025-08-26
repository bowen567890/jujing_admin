<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class InsuranceOrderController extends AdminController
{
    public $statusArr = [
        0=>'待出局',
        1=>'已出局',
    ];
    public $redeemArr = [
        0=>'待赎回',
        1=>'已赎回',
    ];
    protected function grid()
    {
        return Grid::make(InsuranceOrder::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
//             $grid->column('ticket_id');
//             $grid->column('user_ticket_id');
            $grid->column('status')->using($this->statusArr)->label('success');
            $grid->column('is_redeem')->using($this->redeemArr)->label('success');
            $grid->column('insurance');
            $grid->column('ticket_price');
            $grid->column('multiple');
            $grid->column('total_income');
            $grid->column('wait_income');
            $grid->column('over_income');
            $grid->column('next_time');
            $grid->column('redeem_time');
//             $grid->column('ordernum');
            $grid->column('created_at');
//             $grid->column('updated_at')->sortable();
            
            $grid->model()->orderBy('id','desc');
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
                $filter->equal('user.wallet', '用户地址');
                $filter->equal('status')->select($this->statusArr);
                $filter->equal('is_redeem')->select($this->redeemArr);
            });
        });
    }

   
}
