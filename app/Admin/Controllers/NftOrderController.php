<?php

namespace App\Admin\Controllers;

use App\Models\NftOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Models\NftConfig;

class NftOrderController extends AdminController
{
    public $nftRankArr = [];
    public function __construct()
    {
        $nftRankArr = NftConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->nftRankArr = $nftRankArr;
    }
    
    protected function grid()
    {
        return Grid::make(NftOrder::with(['user', 'nftconf']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('nftconf.name',  'NFT等级')->label();
            $grid->column('price');
//             $grid->column('pay_type');
//             $grid->column('ordernum');
            $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title('交易哈希');
                // 自定义图标
                return $this->hash;
            });
        
            $grid->column('created_at');
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
                $filter->equal('nftconf.lv',  'NFT等级')->select($this->nftRankArr);
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
        return Show::make($id, new NftOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('lv');
            $show->field('price');
            $show->field('pay_type');
            $show->field('ordernum');
            $show->field('finish_time');
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
        return Form::make(new NftOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('lv');
            $form->text('price');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('finish_time');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
