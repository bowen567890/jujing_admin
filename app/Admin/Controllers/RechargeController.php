<?php

namespace App\Admin\Controllers;

use App\Models\Recharge;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RechargeController extends AdminController
{
     public $CoinTypeArr = [
        1=>'USDT',
    ];
    protected function grid()
    {
        return Grid::make(Recharge::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
//             $grid->column('main_chain');
            $grid->column('coin_type')->using($this->CoinTypeArr)->label('success');
            $grid->column('num');
//             $grid->column('ordernum');
            $grid->column('hash');
//             $grid->column('date');
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
            });
        });
    }

}
