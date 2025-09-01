<?php

namespace App\Admin\Controllers;

use App\Models\UserNft;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Models\NftConfig;

class UserNftController extends AdminController
{
    public $sourceTypeArr = [
        1=>'注册赠送',
        2=>'合成获得',
        3=>'签到升级',
        4=>'平台购买',
        5=>'推荐获得',
    ];
    public $statusArr = [
        1=>'仓库中',
        2=>'已合成',
        3=>'已升级',
    ];
    
    public $nftRankArr = [];
    public function __construct()
    {
        $nftRankArr = NftConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->nftRankArr = $nftRankArr;
    }
    
    protected function grid()
    {
        return Grid::make(UserNft::with(['user','nftconf']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
//             $grid->column('target_id');
            $grid->column('source_type')->using($this->sourceTypeArr)->label('success');
            $grid->column('nftconf.name',  'NFT等级')->label();
            $grid->column('status');
            $grid->column('upgrade_type');
            $grid->column('sign_date');
            $grid->column('total_day');
            $grid->column('wait_day');
            $grid->column('over_day');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
                $filter->equal('user.wallet', '用户地址');
                $filter->equal('nftconf.lv',  'NFT等级')->select($this->nftRankArr);
            });
        });
    }
}
