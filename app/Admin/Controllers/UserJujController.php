<?php

namespace App\Admin\Controllers;

use App\Models\UserJuj;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserJujController extends AdminController
{
     public $cateArr = [
        1=>'链上增加',
        2=>'链上扣除',
        3=>'余额提币',
        4=>'提币驳回',
        5=>'锁仓释放',
        6=>'推荐奖励',
    ];
     
    protected function grid()
    {
        return Grid::make(UserJuj::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('type')
            ->display(function () {
                $arr = [1=>'收入', 2=>'支出'];
                $msg = $arr[$this->type];
                $colour = $this->type == 1 ? '#21b978' : '#ea5455';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('total');
            //             $grid->column('ordernum');
            //             $grid->column('msg');
            $grid->column('cate')->using($this->cateArr)->label();
            $grid->column('from_user_id');
            //             $grid->column('ma_usdt_price');
            $grid->column('content');
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
                $filter->equal('type')->select([1=>'收入', 2=>'支出']);
                $filter->equal('cate')->select($this->cateArr);
            });
        });
    }
}
