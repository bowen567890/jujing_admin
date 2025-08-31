<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LevelConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use App\Models\MyRedis;
use Illuminate\Support\Facades\Redis;
use App\Units\EthHelper;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\MainCurrency;
use App\Models\RegisterOrderLog;
use App\Models\OrderLog;
use App\Models\UserLockOrder;
use App\Models\NftConfig;
use App\Models\UserNft;

class AuthController extends Controller
{
    
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    
    public function login(Request $request)
    {
        $in = $request->input();
        
        if (!isset($in['wallet']) || !$in['wallet'])  return responseValidateError(__('error.请输入钱包地址'));
        
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        
        if (env('VERIFY_ENABLE')===true)
        {
            $wallet1 = strtolower($wallet);
            if (!isset($in['sign_message']) || !$in['sign_message'])  {
                return responseValidateError(__('error.参数错误'));
            }
            $sign_message = trim($in['sign_message']);
            
            $signVerify = env('SIGN_VERIFY');
            if (EthHelper::signVerify($signVerify, $wallet, $sign_message)==false){
                return responseValidateError(__('error.参数错误'));
                //             return responseValidateError('签名不正确,无法登录');
            }
        }
        
        $wallet = strtolower($wallet);
        //判断是否注册过了，没有就注册一遍
        $lockKey = 'auth:login:'.$wallet;
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 20);
        if(!$lock){
            return responseValidateError(__('error.网络延迟'));
        }
        
        $user = User::query()->where('wallet', $wallet)->first(['id']);
        if (!$user)
        {
            if (!isset($in['code']) || !$in['code']){
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.请通过邀请链接注册登录'));
            }
            $code = trim($in['code']);
            $parent = User::query()->where('code', $code)->select('id','wallet','path','level')->first();
            if (!$parent || !$parent->wallet){
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.推荐人不存在'));
            }
            
//             $http = new Client();
            DB::beginTransaction();
            try
            {
                $validated['parent_id'] = $parent->id;
                $validated['wallet'] = $wallet;
                $validated['path'] = empty($parent->path) ? '-'.$parent->id.'-' : $parent->path.$parent->id.'-';
                $validated['level'] = $parent->level+1;
                $validated['headimgurl'] = 'headimgurl/default.jpg';
                $user = User::create($validated);
                
                //注册赠送算力
                $register_gift_power = intval(config('register_gift_power'));
                if ($register_gift_power>0) {
                    $userModel = new User();
                    $cate = ['cate'=>3, 'msg'=>'注册赠送', 'ordernum'=>get_ordernum()];
                    $userModel->handleUser('power', $user->id, $register_gift_power, 1, $cate);
                }
                
                DB::commit();
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                $MyRedis->del_lock($lockKey);
                //                         var_dump($e->getMessage().$e->getLine());die;
                return responseValidateError(__('error.系统维护'));
            }
        }
        
        $token = 'Bearer '.JWTAuth::fromUser($user);
        $lastKey = 'last_token:'.$user->id;
        $MyRedis->set_key($lastKey, $token);
        
        $MyRedis->del_lock($lockKey);
        return responseJson([
            'token' => $token
        ]);
    }
    
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        
        return responseJson();
    }
    
    /**
     * 注册支付
     */
    public function register(Request $request)
    {
        $in = $request->input();
        
        if (!isset($in['wallet']) || !$in['wallet'])  return responseValidateError(__('error.请输入钱包地址'));
        
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        
        $pay_type = 2;  //支付类型1USDT(链上)2BNB(链上)
        
        $lockKey = 'register:'.$wallet;
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        DB::beginTransaction();
        try
        {
            $is_register = $is_pay = $bnb = 0;
            $contract_address = '';
            $ordernum = get_ordernum();
            $user = User::query()->where('wallet', $wallet)->first(['id']);
            if ($user)
            {
                $is_register = 1;
            }
            else
            {
                $parent = [];
                $is_invitation_register = intval(config('is_invitation_register'));
                if ($is_invitation_register)
                {
                    if (!isset($in['code']) || !$in['code']){
                        $MyRedis->del_lock($lockKey);
                        return responseValidateError(__('error.请通过邀请链接注册登录'));
                    }
                    $code = trim($in['code']);
                    $parent = User::query()
                        ->where('status', 1)
                        ->where('code', $code)
                        ->select('id','wallet','path','level')
                        ->first();
                    if (!$parent){
                        $MyRedis->del_lock($lockKey);
                        return responseValidateError(__('error.推荐人不存在'));
                    }
                }
                else
                {
                    if (isset($in['code']) && $in['code'])
                    {
                        $code = trim($in['code']);
                        $parent = User::query()
                            ->where('status', 1)
                            ->where('code', $code)
                            ->select('id','wallet','path','level')
                            ->first();
                    }
                }
                
                
                $register_pay_usdt = bcadd(config('register_pay_usdt'), '0', 2);
                if (bccomp($register_pay_usdt, '0', 2)>0)
                {
                    $is_pay = 1;
                    $bnbCurrency = MainCurrency::query()->where('id', 2)->first(['rate','contract_address']);
                    $contract_address = $bnbCurrency->contract_address;
                    
                    $bnb = bcdiv($register_pay_usdt, $bnbCurrency->rate, 6);
                    if (bccomp($bnb, '0', 6)<=0) {
                        $MyRedis->del_lock($lockKey);
                        return responseValidateError(__('error.系统维护'));
                    }
                    
                    $Order = new RegisterOrderLog();
                    $Order->ordernum = $ordernum;
                    $Order->wallet = $wallet;
                    $Order->parent_id = $parent ? $parent->id : 0;
                    $Order->price = $register_pay_usdt;
                    $Order->bnb = $bnb;
                    $Order->pay_type = $pay_type;
                    $Order->bnb_price = $bnbCurrency->rate;
                    $Order->save();
                    
                    $OrderLog = new OrderLog();
                    $OrderLog->ordernum = $ordernum;
                    $OrderLog->user_id = 0;
                    $OrderLog->type = 2;    //订单类型1余额提币2注册订单
                    $OrderLog->save();
                }
                else
                {
                    $validated = [];
                    $path = '';
                    $parent_level = 0;
                    if ($parent) {
                        $path = empty($parent->path) ? '-'.$parent->id.'-' : $parent->path.$parent->id.'-';
                        $parent_level = $parent->level;
                    }
                    
                    $validated['parent_id'] = $parent ? $parent->id : 0;
                    $validated['wallet'] = $wallet;
                    $validated['path'] = $path;
                    $validated['level'] = $parent_level+1;
                    $validated['headimgurl'] = 'headimgurl/default.jpg';
                    
                    $user = User::create($validated);
                    $is_register = 1;
                    
                    $puser = User::query()->where('id', $user->parent_id)->first(['id','nft_rank','zhi_num']);
                    
                    $uup = [];
                    //赠送JUJ锁仓
                    $register_give_juj = intval(config('register_give_juj'));
                    if ($register_give_juj>0)
                    {
                        $lock_juj_day = intval(config('lock_juj_day'));
                        $lock_juj_day = $lock_juj_day>0 ? $lock_juj_day : 30;
                        
                        $UserLockOrder = new UserLockOrder();
                        $UserLockOrder->ordernum = $ordernum;
                        $UserLockOrder->user_id = $user->id;
                        $UserLockOrder->total = $register_give_juj;
                        $UserLockOrder->wait_num = $register_give_juj;
                        $UserLockOrder->total_day = $lock_juj_day;
                        $UserLockOrder->wait_day = $lock_juj_day;
                        $UserLockOrder->source_type = 1;   //来源类型1注册赠送2空投赠送
                        $UserLockOrder->save();
                        
                        $uup['juj_lock'] = DB::raw("`juj_lock`+{$register_give_juj}");
                    }
                    
                    $userModel = new User();
                    //赠送NFT
                    $register_give_nft = intval(config('register_give_nft'));
                    $NftConfig = NftConfig::query()->where('lv', $register_give_nft)->first();
                    if ($NftConfig)
                    {
                        $uup['nft_rank'] = $NftConfig->lv;
                        
                        //统计&&日志
                        //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得6合成扣除7签到扣除
                        $map = ['cate'=>1, 'msg'=>'注册赠送', 'ordernum'=>$ordernum];
                        $userModel->handleNftLog($user->id, 1, $NftConfig->lv, 1, $map);
                        
                        $total_day = $NftConfig->upgrade_type==1 ? $NftConfig->upgrade_value : 0;
                        $UserNft = new UserNft();
                        $UserNft->user_id = $user->id;
                        $UserNft->target_id = 0;
                        $UserNft->source_type = 1;  //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得
                        $UserNft->lv = $NftConfig->lv;
                        $UserNft->status = 1;       //状态1仓库中2已合成3已升级
                        $UserNft->upgrade_type = $NftConfig->upgrade_type;
                        $UserNft->total_day = $total_day;
                        $UserNft->wait_day = $total_day;
                        $UserNft->save();
                    }
                    
                    if ($uup) {
                        User::query()->where('id',$user->id)->update($uup);
                    }
                    
                    //上级获得NFT奖励
                    $direct_push_num = intval(config('direct_push_num'));
                    $direct_push_nft = intval(config('direct_push_nft'));
                    if ($direct_push_num>0 && $direct_push_nft>0 && $puser)
                    {
                        $mod = bcmod($puser->zhi_num, $direct_push_num, 0);
                        if ($mod==0)
                        {
                            $NftConfig = NftConfig::query()->where('lv', $direct_push_nft)->first();
                            if ($NftConfig->lv>$puser->nft_rank) {
                                $puser->nft_rank = $NftConfig->lv;
                                $puser->save();
                            }
                            
                            //统计&&日志
                            //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得6合成扣除7签到扣除
                            $map = ['cate'=>5, 'msg'=>'推荐获得', 'ordernum'=>$ordernum];
                            $userModel->handleNftLog($puser->id, 1, $NftConfig->lv, 1, $map);
                            
                            $total_day = $NftConfig->upgrade_type==1 ? $NftConfig->upgrade_value : 0;
                            $UserNft = new UserNft();
                            $UserNft->user_id = $puser->id;
                            $UserNft->target_id = 0;
                            $UserNft->source_type = 5;  //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得
                            $UserNft->lv = $NftConfig->lv;
                            $UserNft->status = 1;       //状态1仓库中2已合成3已升级
                            $UserNft->upgrade_type = $NftConfig->upgrade_type;
                            $UserNft->total_day = $total_day;
                            $UserNft->wait_day = $total_day;
                            $UserNft->save();
                        }
                    }
                }
                
                DB::commit();
            }
            
            $pay_data = [];
            
            $tmp = [];
            $tmp[] = [
                'num' => $bnb,
            ];
            $pay_data[] = [
                'total' => $bnb,
                'contract_address' => $contract_address,
                'list' => $tmp
            ];
            
            $data = [
                'is_register' => $is_register,
                'remarks' => $ordernum,
                'pay_data' => $pay_data
            ];
            $MyRedis->del_lock($lockKey);
            return responseJson($data);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            //                         var_dump($e->getMessage().$e->getLine());die;
            return responseValidateError(__('error.系统维护'));
        }
    }
    
    
    
    /**
     * 注册
     */
    public function isRegister333(Request $request)
    {
        $in = $request->input();
        if (!isset($in['wallet']) || !$in['wallet'])  return responseValidateError(__('error.请输入钱包地址'));
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        //判断是否注册过了，没有就注册一遍
        $lockKey = 'auth:login:'.$wallet;
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError('操作频繁');
        }
        $flag = 1;
        $user = User::where('wallet', $wallet)->first();
        if (!$user){
            $flag = 0;
        }
        $MyRedis->del_lock($lockKey);
        return responseJson([
            'is_register' => $flag
        ]);
        
    }
}
