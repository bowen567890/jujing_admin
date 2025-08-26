<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use GuzzleHttp\Client;
use App\Models\MerchantOrderLog;
use App\Models\PointConfig;
use App\Models\PointOrderLog;
use App\Models\UserPoint;
use App\Models\UserPower;
use App\Models\PointOrder;
use App\Models\NormalNodeOrderLog;
use App\Models\SuperNodeOrderLog;
use App\Models\NormalNodeOrder;
use App\Models\SuperNodeOrder;
use App\Models\NodeConfig;
use App\Models\TicketConfig;
use App\Models\RankConfig;
use App\Models\NodeOrderLog;
use App\Models\NodeOrder;
use App\Models\RechargeLog;
use App\Models\Recharge;

class RechargeController extends Controller
{
    /**
     * 开通节点
     */
    public function recharge(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['num'])) {
            return responseValidateError(__('error.请输入充值金额'));
        }
        $num = @bcadd($in['num'], '0', 2);
        if (bccomp($num, '0', 2)<=0) {
            return responseValidateError(__('error.请输入充值金额'));
        }
        
        
        $pay_type = 1;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
        
        $ordernum = get_ordernum();
        $Order = new RechargeLog();
        $Order->ordernum = $ordernum;
        $Order->user_id = $user->id;
        $Order->main_chain = 1; //主链1BSC2TRON
        $Order->coin_type = 1; //币种1USDT
        $Order->num = $num;
        $Order->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 5;    //订单类型1余额提币2购买节点3购买入场券4缴纳保证金5余额充值
        $OrderLog->save();
        
        $is_two = 0;
        
        $pay_data = [];
        
        $tmp = [];
        $tmp[] = [
            'num' => $num,
        ];
        $pay_data[] = [
            'total' => $num,
            'contract_address' => $usdtCurrency->contract_address,
            'list' => $tmp
        ];
        
        $data = [
            'remarks' => $ordernum,
            'is_chain' => 1,
            'is_two' => $is_two,
            'pay_data' => $pay_data
        ];
        $MyRedis->del_lock($lockKey);
        return responseJson($data);
    }
    
   
    
    public function rechargeLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = Recharge::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
}
