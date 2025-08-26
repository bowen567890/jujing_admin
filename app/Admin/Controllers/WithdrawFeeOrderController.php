<?php

namespace App\Admin\Controllers;

use App\Models\WithdrawFeeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class WithdrawFeeOrderController extends AdminController
{
    public $payTypeArr = [
        4=>'BNB',
    ];
    public $flagArr = [
        0=>'尚未支付',
        1=>'发起申请',
        2=>'余额不足',
    ];
    
    protected function grid()
    {
        return Grid::make(WithdrawFeeOrder::with(['withdraw']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('withdraw_id');
            $grid->column('user_id');
            $grid->column('num');
            $grid->column('flag')->using($this->flagArr)->label('success');
            $grid->column('pay_type')->using($this->payTypeArr)->label();
//             $grid->column('ordernum');
            $grid->column('finish_time');
//             $grid->column('collection_amount_map');
//             $grid->column('collection_address_map');
            $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title('交易哈希');
                // 自定义图标
                return $this->hash;
            });
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('id','desc');
            $grid->model()->where('pay_status', '=', 1);
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
                $filter->equal('flag')->select($this->flagArr);
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
        return Show::make($id, new WithdrawFeeOrder(), function (Show $show) {
            $show->field('id');
            $show->field('withdraw_id');
            $show->field('user_id');
            $show->field('num');
            $show->field('pay_status');
            $show->field('pay_type');
            $show->field('ordernum');
            $show->field('finish_time');
            $show->field('collection_amount_map');
            $show->field('collection_address_map');
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
        return Form::make(new WithdrawFeeOrder(), function (Form $form) {
            $form->display('id');
            $form->text('withdraw_id');
            $form->text('user_id');
            $form->text('num');
            $form->text('pay_status');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('finish_time');
            $form->text('collection_amount_map');
            $form->text('collection_address_map');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
