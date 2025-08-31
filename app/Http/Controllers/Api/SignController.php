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
use App\Models\NftConfig;
use App\Models\NftOrderLog;
use App\Models\NftOrder;
use App\Models\UserNftStat;
use App\Models\UserNft;
use App\Models\UserNftLog;
use App\Models\UserLockOrder;
use App\Models\SignOrder;
use App\Models\SignOrderLog;

class SignController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $data['sign_day_usdt'] = config('sign_day_usdt');
        
        $time = time();
        $date = date('Y-m-d', $time);
        $yDate = date('Y-m-d', strtotime(date('Y-m-d 00:00:00', $time))-3600);
        
        $last_sign_date = $user->last_sign_date;
        $today_sign = 0;
        $continuous_sign = $user->continuous_sign;
        //今日日期不等最后签到日期
        if ($last_sign_date!=$date) //最后签到时间不等于 今日日期
        {
            //不等于昨天
            if ($last_sign_date!=$yDate)
            {
                $continuous_sign = 0;
                $user->continuous_sign = 0;
                $user->save();
            }
        } else {
            $today_sign = 1;
        }
        $data['total_sign'] = $user->total_sign;
        $data['continuous_sign'] = $user->continuous_sign;
        $data['last_sign_date'] = $last_sign_date;
        $data['today_sign'] = $today_sign;
        
        $wait_num = UserLockOrder::query()
            ->where('user_id', $user->id)
            ->where('status', 0)
            ->sum('wait_num');
        $over_num = UserLockOrder::query()
            ->where('user_id', $user->id)
            ->sum('over_num');
        $data['wait_num'] = bcadd($wait_num, '0', 2);
        $data['over_num'] = bcadd($over_num, '0', 2);
        
        //签到NFT
        $NftConfig = NftConfig::GetListCache();
        $NftConfig = array_column($NftConfig, null, 'lv');
        
        $data['nft_sign'] = [];
        $data['nft_sign']['total_day'] = 0;
        $data['nft_sign']['wait_day'] = 0;
        $data['nft_sign']['over_day'] = 0;
        $data['nft_sign']['this_nft'] = '';
        $data['nft_sign']['next_nft'] = '';
        $signNft = UserNft::query()
            ->where('user_id', $user->id)
            ->where('upgrade_type', 1)  //升级类型1签到天数2合成数量
            ->orderBy('wait_day', 'asc')
            ->first();
        if ($signNft) 
        {
            $data['nft_sign']['total_day'] = $signNft->total_day;
            $data['nft_sign']['wait_day'] = $signNft->wait_day;
            $data['nft_sign']['over_day'] = $signNft->over_day;
            $data['nft_sign']['this_nft'] = $NftConfig[$signNft->lv]['name'];
            $data['nft_sign']['next_nft'] = $NftConfig[$NftConfig[$signNft->lv]['next_lv']]['name'];
        }
        
        $days_list = SignOrder::GetMonthListCache();
        $days_list = array_column($days_list, null, 'date');
        
        $time = time();
        $date = date('Y-m-d', $time);
        
        $signList = SignOrder::query()
            ->where('user_id', $user->id)
            ->where('date', '>=', date('Y-m-01'))
            ->get(['id','user_id','date'])
            ->toArray();
        if ($signList) {
            foreach ($signList as $sval) {
                if (isset($days_list[$sval['date']])) {
                    $days_list[$sval['date']]['status'] = 1;
                }
            }
        }
        $days_list[$date]['today'] = 1;
        $data['days_list'] = array_values($days_list);
        
        
        return responseJson($data);
    }
    
    /**
     * 签到
     */
    public function sign(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $pay_type = 1;  //支付类型1USDT(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        
        $time = time();
        $date = date('Y-m-d', $time);
        $isExists = SignOrder::query()
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->exists();
        if ($isExists) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.今日已签到'));
        }
        
        $sign_day_usdt = @bcadd(config('sign_day_usdt'), '0', 2);
        if (bccomp($sign_day_usdt, '0', 2)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $price = $sign_day_usdt;
        
        $ordernum = get_ordernum();
        
        $order = new SignOrderLog();
        $order->ordernum = $ordernum;
        $order->user_id = $user->id;
        $order->price = $price;
        $order->pay_type = $pay_type;
        $order->save();
        
        $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 4;    //订单类型1余额提币2注册订单3购买NFT4每日签到
        $OrderLog->save();
        
        $pay_data = [];
        
        $tmp = [];
        $tmp[] = [
            'num' => $price,
        ];
        $pay_data[] = [
            'total' => $price,
            'contract_address' => $usdtCurrency->contract_address,
            'list' => $tmp
        ];
        
        $data = [
            'remarks' => $ordernum,
            'pay_data' => $pay_data
        ];
        
        $MyRedis->del_lock($lockKey);
        return responseJson($data);
    }
    
    public function signLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = SignOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
}
