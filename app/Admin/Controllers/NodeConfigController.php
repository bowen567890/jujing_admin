<?php

namespace App\Admin\Controllers;

use App\Models\NodeConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;
use App\Models\RankConfig;
use App\Models\TicketConfig;

class NodeConfigController extends AdminController
{
    public $rankArr = [];
    public $ticketArr = [];
    public function __construct() {
        $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->rankArr = array_merge([0=>'V0'], $rankArr);
        
        $ticketArr = TicketConfig::query()->orderBy('ticket_price', 'asc')->pluck('ticket_price', 'id')->toArray();
        
        $this->ticketArr = array_merge([0=>''], $ticketArr);
    }
    
    public $nodeRankArr = [1=>'精英节点',2=>'核心节点',3=>'创世节点'];
    
    protected function grid()
    {
        return Grid::make(new NodeConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('lv')->using($this->nodeRankArr)->label('success');
            $grid->column('price');
            $grid->column('gift_ticket_id')->using($this->ticketArr)->label('success');
            $grid->column('gift_ticket_num');
            $grid->column('gift_rank_id')->using($this->rankArr)->label('success');
//             $grid->column('static_rate');
            $grid->column('stock');
            $grid->column('sales');
        
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
    
    protected function detail($id)
    {
        return Show::make($id, new NodeConfig(), function (Show $show) {
        
            $show->field('created_at');
            $show->field('updated_at');
            $show->disableDeleteButton();
            $show->disableEditButton();
        });
    }

    protected function form()
    {
        return Form::make(new NodeConfig(), function (Form $form) {
//             $form->display('id');
            $form->display('lv')->customFormat(function ($lv) {
                $arr = [1=>'精英节点',2=>'核心节点',3=>'创世节点'];
                return $arr[$lv];
            });
            $form->number('price')->min(1)->required();
            $form->select('gift_ticket_id')->required()->options($this->ticketArr)->default(0);
            $form->number('gift_ticket_num', '入场券数量')->min(0)->required();
            $form->select('gift_rank_id')->required()->options($this->rankArr)->default(0);
//             $form->decimal('static_rate')->placeholder('0.1=10%')->help('静态收益比率(0.1=10%)')->required();
            $form->number('stock')->required();
            
            $form->saving(function (Form $form)
            {
//                 $id = $form->getKey();
//                 $static_rate = @bcadd($form->static_rate, '0', 2);
//                 if (bccomp($static_rate, '1', 2)>0 || bccomp('0', $static_rate, 2)>=0) {
//                     return $form->response()->error('静态收益比率不正确');
//                 }
                
//                 $form->static_rate = $static_rate;
            });
            
            $form->saved(function (Form $form, $result) {
                NodeConfig::SetListCache();
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
        return JsonResponse::make()->success('删除成功')->location('node_config');
    }
}
