<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\SetBalanceNum;
use App\Admin\Actions\Grid\UpdateWallet;
use App\Admin\Actions\Grid\SetManageRank;

use App\Admin\Repositories\User;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

use App\Models\User as UserModel;
use App\Models\RankConfig;
use Dcat\Admin\Http\JsonResponse;
use App\Models\ManageRankConfig;
use App\Models\NftConfig;

class UserController extends AdminController
{
    public $holdRankArr = [
        0 => '否',1 => '是'
    ];
    public $nftRankArr = [];
    public function __construct()
    {
        $nftRankArr = NftConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->nftRankArr = $nftRankArr;
    }
    
    protected function grid()
    {
        return Grid::make(User::with(['parent']), function (Grid $grid) {
            
            $grid->column('id');
            $grid->column('wallet');
//             $grid->column('wallet')->display('点击查看') // 设置按钮名称
//             ->modal(function ($modal) {
//                 // 设置弹窗标题
//                 $modal->title('钱包地址');
//                 // 自定义图标
//                 return $this->wallet;
//             });
            $grid->column('parent.id','上级ID');
            $grid->column('code');
            $grid->column('usdt');
            $grid->column('juj');
            
            $grid->column('nft_rank', 'NFT等级')->using($this->nftRankArr)->label('success');
//             $grid->column('hold_rank')
//             ->display(function () {
//                 $arr = [
//                     0 => '否',
//                     1 => '是',
//                 ];
//                 $msg = $arr[$this->hold_rank];
//                 $colour = $this->hold_rank == 1 ? '#4277cf' : 'gray';
//                 return "<span class='label' style='background:{$colour}'>{$msg}</span>";
//             });
            $grid->column('zhi_num');
            $grid->column('group_num');
            $grid->column('total_sign');
            $grid->column('continuous_sign')->display(function () 
            {
                $time = time();
                $date = date('Y-m-d', $time);
                $yDate = date('Y-m-d', strtotime(date('Y-m-d 00:00:00', $time))-3600);
                
                $last_sign_date = $this->last_sign_date;
                $continuous_sign = $this->continuous_sign;
                //今日日期不等最后签到日期
                if ($last_sign_date!=$date) //最后签到时间不等于 今日日期
                {
                    //不等于昨天
                    if ($last_sign_date!=$yDate)
                    {
                        $continuous_sign = 0;
//                         $user->continuous_sign = 0;
//                         $user->save();
                    }
                }
                return $continuous_sign;
            });
            
//             $grid->column('status','状态')->switch('',true);

            
//             $grid->column('pathlist', '关系树')->display('查看') // 设置按钮名称
//                 ->modal(function ($modal) {
//                     // 设置弹窗标题
//                     $modal->title('关系树');
//                     $path = $this->path;
//                     $parentIds = explode('-',trim($path,'-'));
//                     $parentIds = array_reverse($parentIds);
//                     $parentIds = array_filter($parentIds);
                    
//                     $html = '<table class="table custom-data-table data-table" id="grid-table">
//                                     <thead>
//                                     	  <tr>
//                                     			 <th>上级ID</th>
//                                                  <th>层级</th>
//                                                  <th>等级</th>
//                                     			 <th>地址</th>
//                                     	  </tr>
//                                     </thead>
//                                     <tbody>';
                    
//                     if ($parentIds)
//                     {
//                         $list = UserModel::query()->whereIn('id',$parentIds)->orderBy('level', 'desc')->get(['id','wallet','level','code','rank'])->toArray();
//                         if ($list) {
//                             foreach ($list as $val) {
//                                 $html.= "<tr><td>{$val['id']}</td>";
//                                 $html.= "<td>{$val['level']}</td>";
//                                 $html.= "<td>V{$val['rank']}</td>";
//                                 $html.= "<td>{$val['wallet']}</td>";
//                                 $html.= "</tr>";
//                             }
//                         }
//                     }
                    
//                     $html.= "</tbody></table>";
//                     // 自定义图标
//                     return $html;
//             });
            
            
            $grid->column('created_at','注册时间');
            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);
            
            //如果代发货，显示发货按钮
            $grid->actions(function (Grid\Displayers\Actions $actions) use (&$grid){
                $actions->append(new SetBalanceNum());
                $actions->append(new UpdateWallet());
//                 $actions->append(new SetManageRank());
            });
            
            $grid->disableRowSelector();
            $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->disableDeleteButton();
            $grid->disableCreateButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('wallet');
//                 $filter->equal('status','状态')->radio([0=>'禁用',1=>'有效']);
                $filter->equal('nft_rank')->select($this->nftRankArr);
                $filter->between('created_at','注册时间')->datetime();
            });
        });
    }


    protected function form()
    {
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            $form->display('wallet');
            
            $form->select('rank', '团队等级')->required()->options($this->rankArr)->default(0);
            $form->radio('hold_rank', '保持等级')->required()->options($this->holdRankArr)->default(0);
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                if ($id==1) {
//                     $form->is_branch = 1;
                }
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
        return JsonResponse::make()->success('删除成功')->location('users');
    }

}
