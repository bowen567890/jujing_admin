<?php
namespace App\Admin\Controllers;

use App\Admin\Renderable\UserPowerTable;
use App\Admin\Repositories\User;
use App\Models\LevelConfig;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Tab;
use Dcat\Admin\Widgets\Card;
use App\Models\User as UserModel;
use App\Models\RankConfig;

class UserTreeController extends AdminController
{
    public $holdRankArr = [
        0 => '否',1 => '是'
    ];
    public $rankArr = [];
    public $nodeRankArr = [0=> '', 1=>'精英节点',2=>'核心节点',3=>'创世节点'];
    public function __construct()
    {
        $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->rankArr = array_merge([0=>'V0'], $rankArr);
    }
    
    public function index(Content $content)
    {
        return $content->header('推荐树')->description('推荐树管理')->body($this->grid());
    }

    protected function grid(){
        return Grid::make(User::with([]), function (Grid $grid) {
//            $grid->number();
            $grid->column('wallet')->tree();
            $grid->column('id','ID');
            $grid->column('rank')->using($this->rankArr)->label('success');
            $grid->column('node_rank', '节点等级')->using($this->nodeRankArr)->label('success');
            $grid->column('code');
            $grid->column('usdt');
            $grid->column('zhi_num');
            $grid->column('group_num');
            
//             $grid->column('self_num');
//             $grid->column('team_num');
            $grid->column('total_num', '总单数')->help('个人+团队的单数');
            $grid->column('yeji','业绩')->display(function (){
                
                $big_num = UserModel::query()->where('parent_id', $this->id)->orderBy('total_num', 'desc')->value('total_num');
                $big_num = intval($big_num);
                
                $html = "";
                $html .= "<div style='margin-top: 2px;'>个人单数：" . $this->self_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>团队单数：" . $this->team_num . "</div>";
//                 $html .= "<div style='margin-top: 2px;'>总单数：" . $this->total_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>大区单数：" . $big_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>小区单数：" . $this->small_num . "</div>";
                return $html;
            });
            

//             $grid->column('small_num');
            
            /* 
            $grid->column('wallet', '钱包地址')->display('点击查看') // 设置按钮名称
                ->modal(function ($modal) {
                    // 设置弹窗标题
//                     $modal->title('钱包地址');
                    // 自定义图标
                    return $this->wallet;
            });
              */   
//             $grid->column('wallet', '钱包地址')
//             ->display('点击查看') // 设置按钮名称
//             ->expand(function () {
//                 // 返回显示的详情
//                 // 这里返回 content 字段内容，并用 Card 包裹起来
//                 $card = new Card(null, $this->wallet);
//                 return "<div style='padding:10px 10px 0'>$card</div>";
//             });
            
//             $grid->column('status','状态')->switch('',true);
//             $grid->column('created_at','注册时间');
            $grid->disableRowSelector();

            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id','用户ID');
                $filter->equal('wallet','钱包地址');
//                 $filter->equal('status','状态')->radio([0=>'禁用',1=>'有效']);
//                 $filter->between('created_at','注册时间')->datetime();
            });
        });
    }

}
