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
use App\Models\UserTicket;
use App\Models\InsuranceOrder;

class InsuranceController extends Controller
{
    public function invest(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['id']) || intval($in['id'])<=0) {
            return responseValidateError(__('error.请选择入场券'));
        }
        $id = intval($in['id']);
        
        $pay_type = 1;  //支付类型1USDT(链上)3DOGBEE(链上)
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        DB::beginTransaction();
        try
        {
            $UserTicket = UserTicket::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            if (!$UserTicket) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.请选择入场券'));
            }
            
            if ($UserTicket->status!=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.入场券状态已更新'));
            }
            
            $TicketConfig = TicketConfig::query()->where('id', $UserTicket->ticket_id)->first();
            if (!$TicketConfig || $TicketConfig->insurance<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            
            $user = User::query()->where('id', $user->id)->first(['id','node_rank', 'usdt']);
            
            $insurance = $TicketConfig->insurance;
            if (bccomp($user->usdt, $insurance, 2)<0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.余额不足'));
            }
            
            $ordernum = get_ordernum();
            
            //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
            //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
            $userModel = new User();
            $map = ['ordernum'=>$ordernum, 'cate'=>7, 'msg'=>'支付保证金'];
            $userModel->handleUser('usdt', $user->id, $insurance, 2, $map);
            
            $multiple = @bcadd(config('insurance_multiple'), '0', 2);
            if ($multiple<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            
            $every_income_hour = @bcadd(config('every_income_hour'), '0', 0);
            if ($every_income_hour<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            
            $time = time();
            $next_time = date('Y-m-d H:i:s', $time+$every_income_hour*3600);
            
            $ticket_price = $TicketConfig->ticket_price;
            $total_income = bcmul($ticket_price, $multiple, 2);
            
            $order = new InsuranceOrder();
            $order->ordernum = $ordernum;
            $order->user_id = $user->id;
            $order->ticket_id = $TicketConfig->id;
            $order->user_ticket_id = $UserTicket->id;
            $order->insurance = $insurance;
            $order->multiple = $multiple;
            $order->ticket_price = $ticket_price;
            $order->total_income = $total_income;
            $order->wait_income = $total_income;
            $order->next_time = $next_time;
            $order->save();
            
            $UserTicket->status = 1;    //状态0待使用1已使用2已赠送
            $UserTicket->insurance_id = $order->id;
            $UserTicket->save();
          
            DB::commit();
            $MyRedis->del_lock($lockKey);
            
            return responseJson();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
//             return responseValidateError(__('error.系统维护'));
            return responseValidateError($e->getMessage().$e->getLine());
            //                 var_dump($e->getMessage().$e->getLine());die;
        }
    }
    
   
    
    public function investLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        if (isset($in['status']) && is_numeric($in['status']) && in_array($in['status'], [0,1])) {
            $where['status'] = $in['status'];
        }
        if (isset($in['is_redeem']) && is_numeric($in['is_redeem']) && in_array($in['is_redeem'], [0,1])) {
            $where['is_redeem'] = $in['is_redeem'];
        }
        
        $list = InsuranceOrder::query()
            ->where($where)
            ->orderBy('is_redeem', 'asc')
            ->orderBy('status', 'asc')
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
            
        return responseJson($list);
    }
    
    public function redeem(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['id']) || intval($in['id'])<=0) {
            return responseValidateError(__('error.请选择挖矿记录'));
        }
        $id = intval($in['id']);
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $InsuranceOrder = InsuranceOrder::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (!$InsuranceOrder) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.请选择挖矿记录'));
        }
        
        if ($InsuranceOrder->is_redeem!=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.订单已赎回'));
        }
        
        $ordernum = get_ordernum();
        
        //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
        //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
        $userModel = new User();
        $map = ['ordernum'=>$InsuranceOrder->ordernum, 'cate'=>8, 'msg'=>'赎回保证金'];
        $userModel->handleUser('usdt', $user->id, $InsuranceOrder->insurance, 1, $map);
        
        $InsuranceOrder->is_redeem = 1;
        $InsuranceOrder->next_time = '';
        $InsuranceOrder->redeem_time = date('Y-m-d H:i:s');
        $InsuranceOrder->save();
        
        $MyRedis->del_lock($lockKey);
        
        return responseJson();
    }
}
