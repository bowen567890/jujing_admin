<?php


namespace App\Admin\Renderable;


use App\Models\UserPaymentMethod;
use App\Models\UsersPower;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Models\Administrator;

class UserPowerTable extends LazyRenderable
{

    public function render()
    {
        return Grid::make(UsersPower::query()->where('user_id',$this->payload['key']), function (Grid $grid) {
            $grid->number();
            $grid->column('type','类型')->using([1=>'单币挖矿',2=>'LP挖矿',3=>'SAAS挖矿',4=>'激励算力']);
            $grid->column('power','静态算力');
            $grid->column('dynamic_power','动态算力');

            $grid->disableBatchActions();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableRefreshButton();
            $grid->disableActions();
        });
    }

    public function grid(): Grid
    {

    }
}
