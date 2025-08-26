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

class NodeController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        
        $list = NodeConfig::query()
        ->get(['lv','price','gift_ticket_id','gift_ticket_num','gift_rank_id','static_rate','stock'])
            ->toArray();
        if ($list) 
        {
            $every_income_rate = config('every_income_rate');
            $every_income_rate = $every_income_rate*100;
            foreach ($list as &$val) 
            {
                $val['ticket_price'] = '0';
                
                $TicketConfig = TicketConfig::query()->where('id', $val['gift_ticket_id'])->first();
                if ($TicketConfig) {
                    $val['ticket_price'] = $TicketConfig->ticket_price;
                }
                
                $val['rank_lv'] = '0';
                $RankConfig = RankConfig::query()->where('lv', $val['gift_rank_id'])->first();
                if ($RankConfig) {
                    $val['rank_lv'] = $RankConfig->lv;
                }
                
                $val['stock'] = $val['stock']<=0 ? 0 : $val['stock'];
                $val['static_rate'] = $every_income_rate.'%';
                
            }
        }
        
        return responseJson($list);
    }
    
    /**
     * 开通节点
     */
    public function openNode(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $lv = 1;
        if (isset($in['lv']) && in_array($in['lv'], [1,2,3])) {
            $lv = intval($in['lv']);
        }
        
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
            $user = User::query()->where('id', $user->id)->first(['id','node_rank', 'usdt']);
            if ($user->node_rank>0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.您已经是节点'));
            }
            
            $NodeConfig = NodeConfig::query()->where('lv', $lv)->first();
            if (!$NodeConfig || $NodeConfig->price<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            
            if ($NodeConfig->stock<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.库存不足'));
            }
            
            $price = $NodeConfig->price;
            if (bccomp($user->usdt, $price, 2)<0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.余额不足'));
            }
            
            $ordernum = get_ordernum();
            
            //分类1系统增加2系统扣除3余额提币4提币驳回5余额充值6购买入场券7支付保证金8赎回保证金9开通节点
            //12直推奖励13层级奖励14静态奖励15等级奖励16精英分红17核心分红18创世分红19排名分红
            $userModel = new User();
            $map = ['ordernum'=>$ordernum, 'cate'=>9, 'msg'=>'开通节点'];
            $userModel->handleUser('usdt', $user->id, $NodeConfig->price, 2, $map);
            
            $order = new NodeOrder();
            $order->ordernum = $ordernum;
            $order->user_id = $user->id;
            $order->lv = $NodeConfig->lv;
            $order->price = $NodeConfig->price;
            $order->gift_ticket_id = $NodeConfig->gift_ticket_id;
            $order->gift_ticket_num = $NodeConfig->gift_ticket_num;
            $order->gift_rank_id = $NodeConfig->gift_rank_id;
            $order->static_rate = $NodeConfig->static_rate;
            $order->pay_type = $pay_type;
            $order->save();
            
            $datetime = date('Y-m-d H:i:s');
            $TicketConfig = TicketConfig::query()->where('id', $order->gift_ticket_id)->first();
            if ($TicketConfig && $order->gift_ticket_num>0)
            {
                $TicketData = [];
                for ($i=1; $i<=$order->gift_ticket_num; $i++)
                {
                    $TicketData[] = [
                        'user_id' => $order->user_id,
                        'ticket_id' => $order->gift_ticket_id,
                        'source_type' => 2, //来源1平台购买2平台赠送3用户赠送
                        'ordernum' => $order->ordernum,
                        'created_at' => $datetime,
                        'updated_at' => $datetime
                    ];
                }
                UserTicket::query()->insert($TicketData);
            }
            
            $uup = [];
            $uup['node_rank'] = $order->lv;
            $RankConfig = RankConfig::query()->where('lv', $order->gift_rank_id)->first();
            if ($RankConfig) {
                $uup['rank'] = $RankConfig->lv;
                $uup['hold_rank'] = 1;
            }
            if (bccomp($order->static_rate, '0', 2)>0) {
                $uup['static_rate'] = $order->static_rate;
            }
            User::query()->where('id', $order->user_id)->update($uup);
            
            NodeConfig::query()->where('lv', $order->lv)->update([
                'stock'=> DB::raw("`stock`-1"),
                'sales'=> DB::raw("`sales`+1")
            ]);
          
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
    
   
    
    public function openNodeLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = NodeOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
}
