<?php

namespace App\Admin\Controllers;

use App\Models\Withdraw;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class WithdrawController extends AdminController
{
    public $CoinTypeArr = [
        1=>'USDT',
    ];
    
    protected function grid()
    {
        return Grid::make(Withdraw::with(['user']), function (Grid $grid) {
            $grid->column('id');
//             $grid->column('no');
            $grid->column('user_id','用户ID');
            $grid->column('receive_address');
            $grid->column('num');
            $grid->column('coin_type')->using($this->CoinTypeArr)->label('success');
            $grid->column('fee');
            $grid->column('fee_amount');
            $grid->column('ac_amount');
            $grid->column('status')
            ->display(function () {
                $arr = [
                    0 => '待上链',
                    1 => '已完成',
                    2 => '已拒绝'
                ];
                $msg = $arr[$this->status];
                $colour = $this->status == 0 ? '#edc30e' : ($this->status == 1 ? '#21b978' : '#808080');
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title('交易哈希');
                // 自定义图标
                return $this->hash;
            });
            
            $grid->column('finsh_time');
            $grid->column('created_at')->sortable();
//             $grid->export();

//             $grid->model()->where('fee_status', '=', 1);
            $grid->model()->orderBy('id','desc');
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableRowSelector();
            
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id','用户ID');
//                 $filter->equal('no','订单号');
                $filter->equal('receive_address','收款地址');
                $filter->equal('status', '状态')->select([
                    0 => '待上链',
                    1 => '已完成',
                    2 => '已拒绝'
                ]);
                $filter->equal('coin_type', '币种')->select($this->CoinTypeArr);
            });
        });
    }
}
