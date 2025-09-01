<?php

namespace App\Admin\Controllers;

use App\Models\UserNftStat;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Models\NftConfig;

class UserNftStatController extends AdminController
{
    public $nftRankArr = [];
    public function __construct()
    {
        $nftRankArr = NftConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->nftRankArr = $nftRankArr;
    }
    protected function grid()
    {
        return Grid::make(UserNftStat::with(['user', 'nftconf']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('nftconf.name',  'NFT等级')->label();
            $grid->column('num');
            
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

  
}
