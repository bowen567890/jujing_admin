<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\FundPool;
use App\Models\Withdraw;

class AssignFundPool extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    public $balanceType = [
        1=>'工作室基金(ENTT)',
        2=>'工作室基金(DINO)',
        3=>'NFT分红(ENTT)',
        4=>'NFT分红(DINO)',
        5=>'社区建设基金(ENTT)',
        6=>'社区建设基金(DINO)',
        7=>'手续费分红池(USDT)',
    ];
    public $fieldArr = [
        1=>'studio_entt',
        2=>'studio_dino',
        3=>'nft_entt',
        4=>'nft_dino',
        5=>'community_entt',
        6=>'community_dino',
        7=>'commission_usdt',
    ];
    public $balanceArr = [
        1=>'entt',
        2=>'dino',
        3=>'entt',
        4=>'dino',
        5=>'entt',
        6=>'dino',
        7=>'usdt',
    ];
    
    public $msgArr = [
        1 => [
            'cate' => 16,
            'msg' => '工作室基金'
        ],
        2 => [
            'cate' => 16,
            'msg' => '工作室基金'
        ],
        3 => [
            'cate' => 17,
            'msg' => 'NFT分红奖励'
        ],
        4 => [
            'cate' => 17,
            'msg' => 'NFT分红奖励'
        ],
        5 => [
            'cate' => 19,
            'msg' => '社区建设奖励'
        ],
        6 => [
            'cate' => 19,
            'msg' => '社区建设奖励'
        ],
        7 => [
            'cate' => 13,
            'msg' => '手续费分红'
        ],
    ];
    
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $type = $input['type'];
        
        if (!isset($input['wallet']) || !$input['wallet']) {
            return $this->response()->error('请输入钱包地址');
        }
        $wallet = strtolower($input['wallet']);
        
        $num = @bcadd($input['num'], '0', 6);
        
        $lockKey = 'AssignFundPool';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
      
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        //单币质押超过200,且ENTT,DINO两个币都没有提币过,就有资格分手续费分红池子的U
        if ($type==7) 
        {
            if (bccomp($user->contribution_entt, '200', 6)<0 && bccomp($user->contribution_dino, '200', 6)<0) {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error("单币质押都小于200");
            }
            $isWithdraw = Withdraw::query()
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->whereIn('coin_type', [2,3])
                ->first();
            if ($isWithdraw) 
            {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error("用户已提过币条件不满足");
            }
        }
        
        $balanceTxt  = $this->balanceType[$type];
        $balance  = $this->balanceArr[$type];
        $field  = $this->fieldArr[$type];
        $cate = $this->msgArr[$type]['cate'];
        $msg = $this->msgArr[$type]['msg'];
        if (bccomp($num, '0', 6)>0)
        {
            $fundPool = FundPool::query()->where('id', 1)->first();
            
            if (bccomp($num, $fundPool->$field, 6)>0) {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error("扣除数量大于现有{$balanceTxt}数量");
            }
            
            $userModel = new User();
            $userModel->handleUser($balance, $user->id, $num, 1, ['cate'=>$cate,'msg'=>$msg]);
            
            FundPool::query()->where('id', 1)->update([
                $field => DB::raw("`{$field}`-{$num}")
            ]);
            
        } else {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('操作数量需大于0');
        }
        $MyRedis->del_lock($lockKey);
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }
    
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('type','基金类型')->options($this->balanceType)->required();
        $this->decimal('num', '发放数量')->required();
        $this->text('wallet', '钱包地址')->required()->help('用户钱包地址');
        $this->disableResetButton();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $id = $this->payload['id'] ?? 0;
        
        return [
            'num' => 0,
            'wallet' => '',
            'type' => 1,
        ];
    }
}
