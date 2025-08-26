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
use App\Models\NodeConfig;
use App\Models\TicketConfig;
use App\Models\RankConfig;
use App\Models\NodeOrderLog;
use App\Models\NodeOrder;
use App\Models\TicketOrderLog;
use App\Models\UserTicket;
use App\Models\TicketOrder;

class TicketController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        $list = TicketConfig::GetListCache();
        return responseJson($list);
    }
    
    /**
     * 购买入场券
     */
    public function buy(Request $request)
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
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        DB::beginTransaction();
        try
        {    
            $TicketConfig = TicketConfig::query()->where('id', $id)->first();
            if (!$TicketConfig || $TicketConfig->ticket_price<=0 || $TicketConfig->status!=1) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            
            $num = '1';
            $ticket_price = $TicketConfig->ticket_price;
            $total_price = bcmul($ticket_price, $num, 2);
            
            $user = User::query()->where('id', $user->id)->first(['id','node_rank', 'usdt']);
            if (bccomp($user->usdt, $total_price, 2)<0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.余额不足'));
            }
            
            $ordernum = get_ordernum();
            
            //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
            //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
            $userModel = new User();
            $map = ['ordernum'=>$ordernum, 'cate'=>6, 'msg'=>'购买入场券'];
            $userModel->handleUser('usdt', $user->id, $total_price, 2, $map);
            
            
            $order = new TicketOrder();
            $order->ordernum = $ordernum;
            $order->user_id = $user->id;
            $order->ticket_id = $TicketConfig->id;
            $order->total_price = $total_price;
            $order->num = $num;
            $order->ticket_price = $ticket_price;
            $order->pay_type = $pay_type;
            $order->save();
            
            $datetime = date('Y-m-d H:i:s');
            if ($num>0)
            {
                $TicketData = [];
                for ($i=1; $i<=$num; $i++)
                {
                    $TicketData[] = [
                        'user_id' => $order->user_id,
                        'ticket_id' => $order->ticket_id,
                        'source_type' => 1, //来源1平台购买2平台赠送3用户赠送
                        'ordernum' => $order->ordernum,
                        'created_at' => $datetime,
                        'updated_at' => $datetime
                    ];
                }
                UserTicket::query()->insert($TicketData);
            }
            
            TicketConfig::query()->where('id', $order->ticket_id)->increment('ticket_sale', $order->num);
            
            DB::commit();
            $MyRedis->del_lock($lockKey);
        
            return responseJson();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
            return responseValidateError($e->getMessage().$e->getLine());
            //                 var_dump($e->getMessage().$e->getLine());die;
        }
    }
    
    public function transfer(Request $request)
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
        
        if (!isset($in['wallet']) || !$in['wallet'])  {
            return responseValidateError(__('error.请输入目标地址'));
        }
        
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        
        
        $pay_type = 1;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $UserTicket = UserTicket::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (!$UserTicket) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.请选择入场券'));
        }
        
        if ($UserTicket->status!=0 || $UserTicket->source_type!=2) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.此入场券不可赠送'));
        }
            
        $toUser = User::query()->where('wallet', $wallet)->first(['id']);
        if (!$toUser) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.赠送用户不存在'));
        }
        
        if ($toUser->id==$user->id) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.不能赠送给自己'));
        }
        
        
        $order = new UserTicket();
        $order->ordernum = $UserTicket->ordernum;
        $order->user_id = $toUser->id;
        $order->from_uid = $user->id;
        $order->ticket_id = $UserTicket->ticket_id;
        $order->source_type = 3; //来源1平台购买2平台赠送3用户赠送
        $order->save();
        
        $UserTicket->status = 2;    //状态0待使用1已使用2已赠送
        $UserTicket->from_uid = $toUser->id;
        $UserTicket->save();
        
        $MyRedis->del_lock($lockKey);
        return responseJson();
    }
}
