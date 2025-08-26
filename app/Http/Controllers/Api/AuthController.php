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
     * 注册
     */
    public function isRegister(Request $request)
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
