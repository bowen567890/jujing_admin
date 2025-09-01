<?php

namespace App\Admin\Controllers;

use App\Models\NftConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class NftConfigController extends AdminController
{
    public $lvArr = [0=>'', 1=>'普通',2=>'白银',3=>'黄金',4=>'铂金',5=>'史诗',6=>'传奇'];
    public $statusArr = [0=>'下架', 1=>'上架'];
    public $typeArr = [1=>'签到天数', 2=>'合成数量'];
    
    protected function grid()
    {
        return Grid::make(new NftConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
//             $grid->column('name');
            $grid->column('lv')->using($this->lvArr)->label('success');
            $grid->column('status')->using($this->statusArr)->label();
            $grid->column('price');
            $grid->column('upgrade_type')->using($this->typeArr)->label('success');
            $grid->column('upgrade_value');
            $grid->column('next_lv')->using($this->lvArr)->label();
            $grid->column('fee_rate');
            $grid->column('profit_rate', '盈利税比率');
            $grid->column('gas_add_rate');
            $grid->column('stock');
//             $grid->column('sales');
            $grid->column('image')->image(env('APP_URL').'/uploads/',50,50);
//             $grid->column('created_at');
//             $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('lv','asc');
            
            $grid->disableCreateButton();
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            $grid->disablePagination();	
        });
    }

    protected function form()
    {
        return Form::make(new NftConfig(), function (Form $form) {
//             $form->display('id');
            $form->display('lv')->customFormat(function ($lv) {
                $arr = [0=>'-', 1=>'普通',2=>'白银',3=>'黄金',4=>'铂金',5=>'史诗',6=>'传奇'];
                return $arr[$lv];
            });
            $form->text('name')->required();
            $form->text('name_en')->required();
            $form->radio('status')->required()->options($this->statusArr)->default(0);
            $form->number('price')->required()->min(0);
//             $form->radio('upgrade_type')->required()->options($this->typeArr)->default(1);
            $form->display('upgrade_type')->customFormat(function ($upgrade_type) {
                $arr = [1=>'签到天数', 2=>'合成数量'];
                return $arr[$upgrade_type];
            });
            $form->number('upgrade_value')->min(1)->default(1)->required();
//             $form->select('next_lv')->options([0=>'', 2=>'白银',3=>'黄金',4=>'铂金',5=>'史诗',6=>'传奇'])->default(0);
            $form->decimal('fee_rate')->required()->help('0.1=10%');
            $form->decimal('profit_rate', '盈利税比率')->required()->help('0.1=10%');
            $form->decimal('gas_add_rate')->required()->help('0.1=10%');
            $form->number('stock')->min(0)->required();
//             $form->text('sales');
            $form->image('image')->disk('admin')->uniqueName()->maxSize(10240)->accept('jpg,png,gif,jpeg')->autoUpload();
            
            $form->editor('desc')->required()->disk('admin')->height('600');
            $form->editor('desc_en')->required()->disk('admin')->height('600');
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
//                 $res = NftConfig::query()->where('id', $id)->first();
//                 if ($res->lv==6) {
//                     $form->next_lv = 0;
//                     $form->upgrade_value = 0;
//                 } else {
//                     if ($res->lv>=$form->next_lv) {
//                         return $form->response()->error('下一等级升级等级不正确');
//                     }
//                 }
               
            });
            
            $form->saved(function (Form $form, $result) {
                NftConfig::SetListCache();
            });
            
            $form->disableViewButton();
            $form->disableDeleteButton();
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
    
    /**
     * 删除
     */
    public function destroy($id)
    {
        return JsonResponse::make()->success('删除成功')->location('nft_config');
    }
}
