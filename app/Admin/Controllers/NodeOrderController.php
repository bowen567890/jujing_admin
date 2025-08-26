<?php

namespace App\Admin\Controllers;

use App\Models\NodeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class NodeOrderController extends AdminController
{
    public $nodeRankArr = [0=> '', 1=>'精英节点',2=>'核心节点',3=>'创世节点'];
    protected function grid()
    {
        return Grid::make(NodeOrder::with(['ticket','rank', 'user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('lv', '开通等级')->using($this->nodeRankArr)->label('success');
            $grid->column('price');
            $grid->column('gift_ticket_id')->display(function() {
                if ($this->ticket){
                    return $this->ticket->ticket_price;
                }else{
                    return '0';
                }
            });
            $grid->column('gift_ticket_num');
            $grid->column('gift_rank_id')->display(function() {
                if ($this->rank){
                    return 'V'.$this->rank->lv;
                }else{
                    return 'V0';
                }
            });
            
//             $grid->column('static_rate');
//             $grid->column('pay_type');
//             $grid->column('ordernum');
//             $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
//             ->modal(function ($modal) {
//                 // 设置弹窗标题
//                 $modal->title('交易哈希');
//                 // 自定义图标
//                 return $this->hash;
//             });
        
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
                $filter->equal('lv', '开通等级')->select($this->nodeRankArr);
            });
        });
    }

}
