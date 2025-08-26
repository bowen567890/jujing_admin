<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateDynamicPowerJob;
use App\Models\User;
use App\Models\Withdraw;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Models\MyRedis;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use App\Models\UserCp;
use App\Models\TicketCurrency;
use App\Models\WithdrawWithdrawFeeOrder;
use App\Models\WithdrawFeeOrder;
use App\Models\LuckyPool;

class WithdrawController extends Controller
{
    public function index(Request $request)
    {
        $in = $request->input();
        $user = auth()->user();
        
        $withdrawal_channel_status = intval(config('withdrawal_channel_status'));
        if ($withdrawal_channel_status==0) {
            return responseValidateError(__('error.敬请期待'));
        }
        
        $num = @bcadd($in['num'], '0', 2);
        if ($num<=0) {
            return responseValidateError(__('error.提币数量错误'));
        }
        
        $in['coin_type'] = 1;
//         if ($in['coin_type']==1) {
//             return responseValidateError(__('error.敬请期待'));
//         }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
        $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
//         $withdraw_multiple_num = intval(config('withdraw_multiple_num'));
//         //整倍数判断
//         if ($withdraw_multiple_num>0) {
//             $mod = bcmod($num, $withdraw_multiple_num, 6);
//             if ($mod>0 || $mod!=0) {
//                 $MyRedis->del_lock($lockKey);
//                 $format = __('error.提币数量整倍数');
//                 $msg = sprintf($format, $withdraw_multiple_num);
//                 return responseValidateError($msg);
//             }
//         }
        
        if ($in['coin_type']==1) {
            $coin_type = 'usdt';
            $coinToken = 'USDT';
            $contractAddress = MainCurrency::query()->where('id', 1)->value('contract_address');
            $withdrawFee = @bcadd(config('withdraw_fee_rate'), '0', 6);
        }
        
        $user = User::query()->where('id', $user->id)->first(['id','wallet','usdt']);
        if (bccomp($num, $user->$coin_type, 6)>0){
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.余额不足'));
        }
        
        //特殊情况
        $wNum = intval(config('daily_withdraw_num'));
        if (Withdraw::query()->where('user_id',$user->id)->whereDate('created_at',date('Y-m-d'))->count()>=$wNum){
            $MyRedis->del_lock($lockKey);
            $format = __('error.每日提币次数');
            $msg = sprintf($format, $wNum);
            return responseValidateError($msg);
        }
        
        DB::beginTransaction();
        try
        {
            $wallet = $user->wallet;
            
            $orderNum = get_ordernum();
            $withdraw = new Withdraw();
            $withdraw->ordernum = $orderNum;
            $withdraw->user_id = $user->id;
            $withdraw->receive_address = $wallet;
            $withdraw->num = $num;
            $withdraw->fee = $withdrawFee;
            $withdraw->coin_type = $in['coin_type'];
            $withdraw->fee_amount = bcmul($num, $withdrawFee, 6);
            $withdraw->ac_amount = bcsub($num, $withdraw->fee_amount, 6);
            $withdraw->save();
            
            $userModel = new User();
            //分类1系统增加2系统扣除3余额提币4提币驳回
            $userModel->handleUser($coin_type, $user->id, $num, 2, ['cate'=>3, 'msg'=>'余额提币', 'ordernum'=>$orderNum]);
            
            $OrderLog = new OrderLog();
            $OrderLog->ordernum = $orderNum;
            $OrderLog->user_id = $user->id;
            $OrderLog->type = 1;    //订单类型1提币
            $OrderLog->save();
            
            if ($withdraw->ac_amount>0)
            {
                $http = new Client();
                $data = [
                    'address' => $wallet,
                    'amount' => $withdraw->ac_amount,
                    'contract_address' => $contractAddress,
                    'notify_url' => env('APP_URL').'/api/callback/callback',
                    'remarks' => $orderNum
                ];
                
                $response = $http->post('http://127.0.0.1:9090/v1/bnb/withdraw',[
                    'form_params' => $data,
                    'timeout' => 10,
                    'verify' => false
                ]);
                $result = $response->getBody()->getContents();
                if (!is_array($result)) {
                    $result = json_decode($result, true);
                }
                if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200)
                {
                    DB::rollBack();
                    //                     Log::channel('withdraw')->info('提交提币申请失败');
                    return responseValidateError(__('error.网络异常'));
                } else {
                    Log::channel('withdraw')->info('提交提币申请'.var_export($data, true).'---'.var_export($result, true));
                }
            }
            
            DB::commit();
            $MyRedis->del_lock($lockKey);
            return responseJson();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            return responseJsonAsServerError($e->getMessage().$e->getLine());
        }
    }

    public function list(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        if (isset($in['coin_type']) && in_array($in['coin_type'], [1,2])) {
            $where['coin_type'] = intval($in['coin_type']);
        }
        
        $list = Withdraw::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','ordernum','status','coin_type','num','fee','fee_amount','ac_amount','finsh_time','hash','created_at'])
            ->toArray();
        
        if ($list) {
            foreach ($list as &$v) {
                $v['status_txt'] = __('error.提币状态'.$v['status']);
            }
        }
        return responseJson($list);
    }
}
