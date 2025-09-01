<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MyRedis;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\Withdraw;
use App\Models\MainCurrency;
use App\Models\Config;
use App\Models\UserUsdt;
use App\Models\RankConfig;
use App\Models\SignOrderLog;
use App\Models\SignOrder;
use App\Models\EncryptionServiceModel;
use App\Models\PoolConfig;
use App\Models\RegisterOrderLog;
use App\Models\RegisterOrder;
use App\Models\UserLockOrder;
use App\Models\NftConfig;
use App\Models\UserNft;
use App\Models\NftOrderLog;
use App\Models\NftOrder;
use App\Models\TeamGasConfig;


class CallbackController extends Controller
{
    protected static $encrypter;
    /**
     * 初始化加密服务
     */
    public static function init()
    {
        if (!self::$encrypter) {
            self::$encrypter = app(EncryptionServiceModel::class);
        }
    }
    
    //订单回调
    public function callback(Request $request)
    {
        $in = $request->input();
        $ordernum = isset($in['remarks']) && $in['remarks'] ? $in['remarks'] : '';
        Log::channel('order_callback')->info('收到回调', $in);
        
        $order = OrderLog::query()->where('ordernum', $ordernum)->first();
        if (!$order) {
            return responseValidateError('订单不存在');
        }
        $order->content = json_encode($in);
        $order->save();
        
        //订单类型1余额提币2注册订单3购买NFT
        if ($order->type==1){
            $this->withdraw($in);
        }  
        else if ($order->type==2) {
            $this->userRegister($in);
        } 
        else if ($order->type==3) {
            $this->buyNft($in);
        } else if ($order->type==4) {
            $this->signOrder($in);
        }
        
        return responseValidateError('订单未找到');
    }
    
    //注册订单
    private function userRegister($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:userRegister:'.$ordernum;
        $MyRedis = new MyRedis();
//                                         $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('buy_node')->info('上锁失败', $in);
            die;
        }
        
        $order = RegisterOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('register_order')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='BNB')
        {
            Log::channel('register_order')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 6);
        
        //支付类型1USDT(链上)
        if (bccomp($order->bnb, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('register_order')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            $wlockKey = 'userRegister:'.$order->wallet;
            $wLock = $MyRedis->add_lock($wlockKey, 15); 
            if ($wLock) 
            {
                DB::beginTransaction();
                try
                {
                    $user = User::query()->where('wallet', $order->wallet)->first(['id']);
                    if (!$user)
                    {
                        $parent = User::query()->where('id', $order->parent_id)->select('id','wallet','path','level')->first();
                        
                        $validated = [];
                        $path = '';
                        $parent_level = 0;
                        if ($parent) {
                            $path = empty($parent->path) ? '-'.$parent->id.'-' : $parent->path.$parent->id.'-';
                            $parent_level = $parent->level;
                        }
                        
                        $validated['parent_id'] = $parent ? $parent->id : 0;
                        $validated['wallet'] = $order->wallet;
                        $validated['path'] = $path;
                        $validated['level'] = $parent_level+1;
                        $validated['headimgurl'] = 'headimgurl/default.jpg';
                        
                        $user = User::create($validated);
                        
                        $puser = User::query()->where('id', $user->parent_id)->first(['id','nft_rank','zhi_num']);
                        
                        $time = time();
                        $datetime = date('Y-m-d H:i:s', $time);
                        
                        $order->pay_status = 1;
                        $order->user_id = $user->id;
                        $order->hash = $hash;
                        $order->finish_time = $datetime;
                        $order->save();
                        
                        $RegisterOrder = new RegisterOrder();
                        $RegisterOrder->ordernum = $order->ordernum;
                        $RegisterOrder->user_id = $user->id;
                        $RegisterOrder->wallet = $order->wallet;
                        $RegisterOrder->parent_id = $order->parent_id;
                        $RegisterOrder->price = $order->price;
                        $RegisterOrder->bnb = $order->bnb;
                        $RegisterOrder->pay_type = $order->pay_type;
                        $RegisterOrder->bnb_price = $order->bnb_price;
                        $RegisterOrder->hash = $hash;
                        $RegisterOrder->save();
                        
                        $uup = [];
                        //赠送JUJ锁仓
                        $register_give_juj = intval(config('register_give_juj'));
                        if ($register_give_juj>0)
                        {
                            $lock_juj_day = intval(config('lock_juj_day'));
                            $lock_juj_day = $lock_juj_day>0 ? $lock_juj_day : 30;
                            
                            $UserLockOrder = new UserLockOrder();
                            $UserLockOrder->ordernum = $order->ordernum;
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
                            $map = ['cate'=>1, 'msg'=>'注册赠送', 'ordernum'=>$order->ordernum];
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
                                $map = ['cate'=>5, 'msg'=>'推荐获得', 'ordernum'=>$order->ordernum];
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
                    
                    $this->setOrderStatus($ordernum, 1);
                    
                    DB::commit();
                }
                catch (\Exception $e)
                {
                    DB::rollBack();
                    Log::channel('register_order')->info('回调失败', $in);
                    
                    //                 var_dump($e->getMessage().$e->getLine());die;
                }
            }
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    
    private function signOrder($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:signOrder:'.$ordernum;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('sign_order')->info('上锁失败', $in);
            die;
        }
        
        $order = SignOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('sign_order')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='USDT')
        {
            Log::channel('sign_order')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 6);
        
        //支付类型1USDT(链上)
        if (bccomp($order->price, $amount, 6)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('sign_order')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            
            $sKey = 'signOrder:user:'.$order->user_id;
//             $MyRedis->del_lock($sKey);
            $sLock = $MyRedis->add_lock($sKey, 15);
            if(!$sLock){
                Log::channel('sign_order')->info('用户上锁失败', $in);
                die;
            }
            
            DB::beginTransaction();
            try
            {
                $time = time();
                $date = date('Y-m-d', $time);
                $datetime = date('Y-m-d H:i:s', $time);
                $ydate = date('Y-m-d', $time-86400);
                
                $order->date = $date;
                $order->pay_status = 1;
                $order->hash = $hash;
                $order->finish_time = $datetime;
                $order->save();
                
                $userModel = new User();
                
                //判断今日 是否签到
                $exists = SignOrder::query()
                    ->where('user_id', $order->user_id)
                    ->where('date', $date)
                    ->exists();
                if (!$exists) 
                {
                    $nft_rank = 0;
                    $user = User::query()
                        ->where('id', $order->user_id)
                        ->first(['id','parent_id','nft_rank','total_sign','continuous_sign','last_sign_date']);
                    
                    $NftConfig = NftConfig::GetListCache();
                    $NftConfig = array_column($NftConfig, null, 'lv');
                        
                    //NFT签到升级
                    $waitNftList = UserNft::query()
                        ->where('user_id', $order->user_id)
                        ->where('status', 1)        //状态1仓库中2已合成3已升级
                        ->where('upgrade_type', 1)  //升级类型1签到天数2合成数量
                        ->get(['id','user_id','lv','wait_day','over_day'])
                        ->toArray();
                    if ($waitNftList) 
                    {
                        foreach ($waitNftList as $nval) 
                        {
                            $nup = [];
                            $wait_day = $nval['wait_day']-1;
                            $over_day = $nval['over_day']+1;
                            if ($wait_day<=0) 
                            {
                                $nup['status'] = 3;
                                
                                if ($NftConfig && isset($NftConfig[$nval['lv']])) 
                                {
                                    $thisNft = $NftConfig[$nval['lv']];
                                    if ($thisNft['next_lv']>0 && isset($NftConfig[$thisNft['next_lv']])) 
                                    {
                                        $nextNft = $NftConfig[$thisNft['next_lv']];
                                        
                                        //统计&&日志
                                        //来源1注册赠送2合成获得3签到升级获得4平台购买5推荐获得6合成扣除7签到升级扣除
                                        $map = ['cate'=>7, 'msg'=>'签到升级扣除', 'ordernum'=>$order->ordernum];
                                        $userModel->handleNftLog($order->user_id, 1, $thisNft['lv'], 2, $map);
                                        
                                        $map = ['cate'=>3, 'msg'=>'签到升级获得', 'ordernum'=>$order->ordernum];
                                        $userModel->handleNftLog($order->user_id, 1, $nextNft['lv'], 1, $map);
                                        
                                        $total_day = $nextNft['upgrade_type']==1 ? $nextNft['upgrade_value'] : 0;
                                        $UserNft = new UserNft();
                                        $UserNft->user_id = $order->user_id;
                                        $UserNft->target_id = 0;
                                        $UserNft->source_type = 2;  //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得
                                        $UserNft->lv = $nextNft['lv'];
                                        $UserNft->status = 1;       //状态1仓库中2已合成3已升级
                                        $UserNft->upgrade_type = $nextNft['upgrade_type'];
                                        $UserNft->total_day = $total_day;
                                        $UserNft->wait_day = $total_day;
                                        $UserNft->save();
                                        
                                        $nup['target_id'] = $UserNft->id;
                                        
                                        if ($nextNft['lv']>$nft_rank) {
                                            $nft_rank = $nextNft['lv'];
                                        }
                                    }
                                }
                            }
                            $nup['wait_day'] = $wait_day;
                            $nup['over_day'] = $over_day;
                            UserNft::query()->where('id', $nval['id'])->update($nup);
                        }
                    }
                    
                    $juj = '0';
                    //锁仓释放
                    $zhi_lock_rate = @bcadd(config('zhi_lock_rate'), '0', 6);
                    
                    $llist = UserLockOrder::query()
                        ->where('user_id', $user->id)
                        ->where('status', 0)    //释放状态0释放中1已完结
                        ->get(['id','user_id','total','wait_num','over_num','actual_num','total_day','wait_day','ordernum'])
                        ->toArray();
                    if ($llist) 
                    {
                        foreach ($llist as $lval) 
                        {
                            $outNum = bcdiv($lval['total'], $lval['total_day'], 6);
                            $outNum = bccomp($lval['wait_num'], $outNum, 6)>0 ? $outNum : $lval['wait_num'];
                            
                            $lup = [];
                            $wait_day = $lval['wait_day']-1;
                            if ($wait_day<=0) {
                                $lup['status'] = 1;
                            }
                            $lup['wait_day'] = $wait_day;
                            $lup['wait_num'] = bcsub($lval['wait_num'], $outNum, 6);
                            $lup['over_num'] = bcadd($lval['over_num'], $outNum, 6);
                            $lup['actual_num'] = bcadd($lval['actual_num'], $outNum, 6);
                            $lup['sign_date'] = $date;
                            
                            UserLockOrder::query()->where('id', $lval['id'])->update($lup);
                            
                            User::query()->where('id', $lval['user_id'])->decrement('juj_lock', $outNum);
                            
                            if (bccomp($outNum, '0', 6)>0) 
                            {
                                $juj = bcadd($juj, $outNum, 6);
                                //分类1系统增加2系统扣除3余额提币4提币驳回5锁仓释放
                                $map = ['cate'=>5, 'msg'=>'锁仓释放', 'ordernum'=>$lval['ordernum']];
                                $userModel->handleUser('juj', $lval['user_id'], $outNum, 1, $map);
                                
                                if ($user->parent_id>0 && bccomp($zhi_lock_rate, '0', 6)>0) 
                                {
                                    $zhiNum = bcmul($outNum, $zhi_lock_rate, 6);
                                    if (bccomp($zhiNum, '0', 6)>0) 
                                    {
                                        //分类1系统增加2系统扣除3余额提币4提币驳回5锁仓释放6推荐奖励
                                        $map = ['cate'=>6, 'msg'=>'推荐奖励', 'ordernum'=>$lval['ordernum'], 'from_user_id'=>$lval['user_id']];
                                        $userModel->handleUser('juj', $user->parent_id, $zhiNum, 1, $map);
                                    }
                                }
                            }
                        }
                    }
                    
                    //返利团队 Gas
                    if ($user->parent_id) 
                    {
                        $puser = User::query()->where('id', $user->parent_id)->first(['id','zhi_num','nft_rank']);
                        $gas_rate = TeamGasConfig::GetGasRate($puser->zhi_num);
                        
                        //NFT等级团队gas加成
                        if (isset($NftConfig[$puser->nft_rank])) {
                            $gas_rate = bcadd($gas_rate, $NftConfig[$puser->nft_rank]['gas_add_rate'], 6);
                        }
                        
                        if (bccomp($gas_rate, '0', 6)>0) 
                        {
                            $gasNum = bcmul($order->price, $gas_rate, 6);
                            if (bccomp($gasNum, '0', 6)>0) {
                                //分类1系统增加2系统扣除3余额提币4提币驳回5团队返利GAS
                                $map = ['cate'=>5, 'msg'=>'团队返利GAS', 'ordernum'=>$order->ordernum, 'from_user_id'=>$user->id];
                                $userModel->handleUser('usdt', $user->parent_id, $gasNum, 1, $map);
                            }
                        }
                    }
                  
                    $SignOrder = new SignOrder();
                    $SignOrder->ordernum = $order->ordernum;
                    $SignOrder->user_id = $order->user_id;
                    $SignOrder->date = $date;
                    $SignOrder->price = $order->price;
                    $SignOrder->juj = $juj;
                    $SignOrder->pay_type = $order->pay_type;
                    $SignOrder->hash = $hash;
                    $SignOrder->save();
                    
                    //用户签到统计
                    $user->total_sign = $user->total_sign+1;
                    if ($ydate==$user->last_sign_date) {
                        $user->continuous_sign = $user->continuous_sign+1;
                    } else {
                        $user->continuous_sign = 1;
                    }
                    $user->last_sign_date = $date;
                    //用户等级升级
                    if ($nft_rank>$user->nft_rank) {
                        $user->nft_rank = $nft_rank;
                    }
                    $user->save();
                }
                
                $this->setOrderStatus($ordernum, 1);
                
                DB::commit();
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                $MyRedis->del_lock($sKey);
                Log::channel('sign_order')->info('回调失败222', $in);
//                                 var_dump($e->getMessage().$e->getLine());die;
            }
            $MyRedis->del_lock($sKey);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    //开通商家
    private function buyNft($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:buyNft:'.$ordernum;
        $MyRedis = new MyRedis();
        //                                                 $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('buy_nft')->info('上锁失败', $in);
            die;
        }
        
        $order = NftOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('buy_nft')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='USDT')
        {
            Log::channel('buy_nft')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 2);
        
        //支付类型1USDT(链上)
        if (bccomp($order->price, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('buy_nft')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            
            DB::beginTransaction();
            try
            {
                $time = time();
                $datetime = date('Y-m-d H:i:s', $time);
                
                $order->pay_status = 1;
                $order->hash = $hash;
                $order->finish_time = $datetime;
                $order->save();
                
                $NftOrder = new NftOrder();
                $NftOrder->ordernum = $order->ordernum;
                $NftOrder->user_id = $order->user_id;
                $NftOrder->lv = $order->lv;
                $NftOrder->price = $order->price;
                $NftOrder->pay_type = $order->pay_type;
                $NftOrder->hash = $hash;
                $NftOrder->save();
                
                NftConfig::query()->where('lv', $order->lv)->update([
                    'stock'=> DB::raw("`stock`-1"),
                    'sales'=> DB::raw("`sales`+1")
                ]);
                
                
                $NftConfig = NftConfig::query()->where('lv', $order->lv)->first();
                $user = User::query()->where('id', $order->user_id)->first(['id','nft_rank']);
                
                if ($NftConfig->lv>$user->nft_rank) {
                    $user->nft_rank = $NftConfig->lv;
                    $user->save();
                }
                
                $userModel = new User();
                //统计&&日志
                //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得6合成扣除7签到扣除
                $map = ['cate'=>4, 'msg'=>'平台购买', 'ordernum'=>$order->ordernum];
                $userModel->handleNftLog($user->id, 1, $NftConfig->lv, 1, $map);
                
                $total_day = $NftConfig->upgrade_type==1 ? $NftConfig->upgrade_value : 0;
                $UserNft = new UserNft();
                $UserNft->user_id = $user->id;
                $UserNft->target_id = 0;
                $UserNft->source_type = 4;  //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得
                $UserNft->lv = $NftConfig->lv;
                $UserNft->status = 1;       //状态1仓库中2已合成3已升级
                $UserNft->upgrade_type = $NftConfig->upgrade_type;
                $UserNft->total_day = $total_day;
                $UserNft->wait_day = $total_day;
                $UserNft->save();
                
                $this->setOrderStatus($ordernum, 1);
                
                DB::commit();
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                Log::channel('buy_nft')->info('回调失败', $in);
                
                //                 var_dump($e->getMessage().$e->getLine());die;
            }
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    private function withdraw($in)
    {
        $data = $in;
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:withdraw:'.$in['remarks'];
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            Log::channel('withdraw_callback')->info('回调上锁失败', $in);
            echo '上锁失败';
            die;
        }
        
        $withdraw = Withdraw::query()
            ->where('ordernum', $in['remarks'])
//             ->where('fee_status', 1)
            ->first();
        if (!$withdraw){
            Log::channel('withdraw_callback')->info('未找到数据无法继续');
            $MyRedis->del_lock($lockKey);
            exit();
        }
        if ($withdraw->status!=0){
            Log::channel('withdraw_callback')->info('数据已被处理，无需继续处理');
            $MyRedis->del_lock($lockKey);
            exit();
        }
        
        $userModel = new User();
        DB::beginTransaction();
        try
        {
            $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
            if ($data['status']==5)
            {
                $withdraw->status = 1;
                $withdraw->finsh_time = date('Y-m-d H:i:s');
                $withdraw->hash = $hash;
                $withdraw->save();
                
                if (bccomp($withdraw->fee_amount, '0', 6)>0) {
                    PoolConfig::query()
                        ->where('type', 1)    //类型1提现池子,2精英池子,3核心池子,4创世池子,5排名池子
                        ->increment('pool', $withdraw->fee_amount);
                }
                
                $this->setOrderStatus($ordernum, 1);
                DB::commit();
                $MyRedis->del_lock($lockKey);
            }
            else if ($data['status']==6)
            {
                if ($withdraw->coin_type==1) {
                    $table = 'usdt';
                }
                
                $userModel->handleUser($table, $withdraw->user_id, $withdraw->num, 1, ['cate'=>4, 'msg'=>'提币驳回', 'ordernum'=>$withdraw->ordernum]);
                
                $withdraw->status = 2;
                $withdraw->finsh_time = date('Y-m-d H:i:s');
                $withdraw->hash = $hash;
                $withdraw->save();
                
                $this->setOrderStatus($ordernum, 1);
                DB::commit();
                $MyRedis->del_lock($lockKey);
            }
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            Log::channel('withdraw_callback')->info('提币回调异常');
        }
        
        $MyRedis->del_lock($lockKey);
        echo '提币成功';
        die;
    }

    /**
     * 修改订单状态
     */
    protected function setOrderStatus($ordernum, $status=1) {
        OrderLog::query()->where('ordernum', $ordernum)->where('ordernum', $ordernum)->update(['status'=>$status]);
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function searchPrice(Request $request)
    {
        
        
//         $a = '0.09335684145031986';
//         $b = '0.001234744507092474';
        
//         $price = bcdiv($a, $b, 6);
//         var_dump($price);die;
//         try
//         {
//             $coinId = 'bitcoin';
//             $json = file_get_contents("https://api.coincap.io/v2/assets/{$coinId}");
//             if (is_string($json) && $json)
//             {
//                 $newData = [];
//                 $jsonArr = json_decode($json, true);
//                 if (is_array($jsonArr) && $jsonArr && isset($jsonArr['data']) && is_array($jsonArr['data']) && $jsonArr['data'] && isset($jsonArr['data']['priceUsd']))
//                 {
                    
//                     var_dump($jsonArr['data']['priceUsd']);die;
//                 }
//             }
//         }
//         catch (\Exception $e)
//         {
//             Log::channel('token_price')->info('获取价格失败:'.$e->getMessage().$e->getLine());
//         }
        
//     $MyRedis = new MyRedis();
//     $list = User::query()->get(['id'])->toArray();
//     foreach ($list as $val) {
//         $lastKey = 'last_token:'.$val['id'];
//         $MyRedis->del_lock($lastKey);
//     }
//         var_dump(888888);die;
        



//方法1
//https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token=0x55d398326f99059ff775485246999027b3197955&to_token=0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657&chain=bsc&max_hops=3&max_routes=6&protocol=v3
    
    $client = new Client();
//     $response = $client->get('https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token=0x55d398326f99059ff775485246999027b3197955&to_token=0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657&chain=bsc&max_hops=3&max_routes=6&protocol=v3');
//     var_dump($response->getBody()->getContents());die;

    $usdt = '11515443.313273482';
    $bnb1 = '21111.028221891538';
    
    $bnb2 = '0.031607639676630286';
    $vv = '34179387.1896077';
    
    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
//     string(14) "545.4705091688"
//     string(13) "17.2410353080"
//     string(12) "0.0000005044"
//     var_dump($bnbUsdtPrice,$usdtNum,$vvPrice);

    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
    
    
    //方法2通过
    $usdt = '11528569768479880436806829';
    $bnb1 = '21088073398281325679560';
    
    $bnb2 = '1234744507092474';
    $vv = '93356841450319864';
    
    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
    
    
    
        $in = $request->input();
        $token3 = '';
        $token1 = $in['token1'];
        $token2 = $in['token2'];
        if (isset($in['token3'])) {
            $token3 = $in['token3'];
        }
        
        try
        {
            $queryData['token1'] = $token1;
            $queryData['token2'] = $token2;
            $path[] = $queryData['token1'];
            $path[] = $queryData['token2'];
            if ($token3) {
                $queryData['token3'] = $token3;
                $path[] = $queryData['token3'];
            }
            $queryData['token1Decimals'] = 18;
            $queryData['token2Decimals'] = 18;
            $queryData['token3Decimals'] = 18;
            $token1Decimals = $queryData['token1Decimals'];
            $token2Decimals = $queryData['token2Decimals'];
            $token3Decimals = $queryData['token3Decimals'];
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/getCoinPrice',[
                'json' => [
                    'route_address' => '0x10ed43c718714eb63d5aa57b78b54704e256024e',    //固定不变
                    'amount_in_decimals' => $token1Decimals,
                    'path' => $path
                ]
            ]);
          
            $result = json_decode($response->getBody()->getContents(),true);
            $result['path'] = $path;
            $result['amount_in_decimals'] = $token1Decimals;
            
            echo json_encode($result);die;
            $price =  empty($result['data']) ? 0 : number_format($result['data'][count($result['data'])-1], $token2Decimals, '.', '');
            $price =  sprintf('%.10f',$price/pow(10,$token2Decimals));
            var_dump($price);die;
        }catch (\Exception $e){
            var_dump($e->getMessage().$e->getLine());
        }
    }
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function lpInfo(Request $request)
    {
        $usdtContractAddress = env('USDT_ADDRESS');
        $busdContractAddress = env('BUSD_ADDRESS');
        
        try
        {
            $in = $request->input();
            $contract_address = trim($in['contract_address']);
            $contract_address = strtolower($contract_address);
            
//             $currency = MainCurrency::query()
//                 ->where('name', '=', 'IDO-LP')
//                 ->first(['name','contract_address','precision']);
//             $contract_address = $currency->contract_address;
            
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            /*
             $result = [
             'code' => 200,
             'data' => [
             'reserve0' => 1600000000000000000,
             'reserve1' => 6257063425359314877,
             'totalSupply' => 3162277660168379331,
             ],
             ];
             */
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']))
            {
                Log::channel('lp_info')->info('查询LP信息失败');
            }
            else
            {
                var_dump($result);die;
                
                
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                    $coin_price = @bcdiv($result['data']['reserve1'], $result['data']['reserve0'], 10);
                } else {
                    $coin_price = @bcdiv($result['data']['reserve0'], $result['data']['reserve1'], 10);
                }
                
                var_dump($coin_price, $result);die;
            }
            
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询LP信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function lpInfov3(Request $request)
    {
        
        try
        {
            $in = $request->input();
            $contract_address = trim($in['contract_address']);
            $contract_address = strtolower($contract_address);
            
            $is_fan = intval($in['is_fan']);
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lp3Info',[
                'form_params' => [
                    'contract_address' => $contract_address,
                    'is_fan' => $is_fan
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            var_dump($is_fan,$contract_address,$result);die;
            /*
             $result = [
                  'code' => 200,
                  'data' => [
                        'token0' => '0x7130d2A12B9BCbFAe4f2634d864A1Ee1Ce3Ead9c',
                        'token0Fee' => '57589887794532494',
                        'token1' => env('BUSD_ADDRESS'),
                        'token1Fee' => '1675178344085188180815',
                      ],
                  'msg' => 'success'
                ];
             */
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['token0Fee']) || !isset($result['data']['token1Fee']))
            {
                Log::channel('lp_info')->info('查询LP信息失败');
            }
            else
            {
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                if ($token1==$usdtContractAddress) {
                    $coin_price = @bcdiv($result['data']['token1Fee'], $result['data']['token0Fee'], 10);
                } else {
                    $coin_price = @bcdiv($result['data']['token0Fee'], $result['data']['token1Fee'], 10);
                }
                var_dump($coin_price, $result,$result['data']['token0Fee'],$result['data']['token1Fee']);die;
            }
            
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询LP信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    
    
    
    /**
     * 新版本查询自动买币结果
     */
    public function getTransactionDetail(Request $request)
    {
        try
        {
            $in = $request->input();
            $ordernum = isset($in['ordernum']) && $in['ordernum'] ? $in['ordernum'] : '';
            if (!$ordernum) {
                echo 22222;die;
            }
            
            $client = new Client();
            $response = $client->post('127.0.0.1:9099/getTransactionDetail',[
                'form_params' => [
                    'contract_address' => env('RECHARGE_CONTRACT_ADDRESS'),   //查询自动买币的充值合约地址
                    'order_no' => $ordernum,
                ],
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['out_num']))
            {
                Log::channel('auto_trade_detail')->info('查询自动买币信息失败', $result);
            }
            else
            {
                $pows = pow(10,18);
                $amount = @bcadd($result['data']['out_num'], '0', 6);
                $amount = bcdiv($amount, $pows, 6);    //钱包系统返回来要除以18位
            }
            var_dump($amount,$result);die;
            echo json_encode($result);die;
        }
        catch (\Exception $e)
        {
            Log::channel('auto_trade_detail')->info('查询自动买币信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    /**
     * 释放分红提币
     */
    public function reptile()
    {
        require_once(__DIR__.'/Snoopy.class.php');
        
        $contractAddress = '0x346827CdaA4947f89cB1009cDC4d9473FBc8Bdaa';
        $snoopy = new Snoopy;
        $page = 1;  
        $url = "https://longswap.app/swap?outputCurrency0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657";
//         $url = "https://bscscan.com/token/generic-tokenholders2?a={$contractAddress}&sid=c36547018faca9a52888b6b654edb21a&m=normal&s=6198121588559079236743484&p={$page}";
        $data = $this->getData($snoopy, $url, true);
        $list = $data['list'];
        if($data['totalPage']>0 && $data['totalPage']>$page && $page!=0)
        {
            $diffNum = $data['totalPage']>=10 ? 10 : $data['totalPage'];
            for ($i=2; $i<=$diffNum; $i++) {
                $pp = $i;
//                 $url = "https://bscscan.com/token/generic-tokentxns2?contractAddress={$contractAddress}&mode=&sid=c36547018faca9a52888b6b654edb21a&m=normal&p={$pp}";
                $url = "https://bscscan.com/token/generic-tokenholders2?a={$contractAddress}&sid=c36547018faca9a52888b6b654edb21a&m=normal&s=6198121588559079236743484&p={$pp}";
                $tmp = $this->getData($snoopy, $url, true);
                $list = array_merge($list, $tmp['list']);
                usleep(100000);//等待100ms
            }
        }
        
        if ($list) {
            $newData = [];
            foreach ($list as $val) {
               
            }
        }
        
        echo json_encode($newData);die;
    }
    
    public function getData($snoopy, $url, $getPage=false)
    {
        $arr['totalPage'] = 0;
        $arr['list'] = [];
        
        $snoopy->agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36";
        $snoopy->referer = "https://bscscan.com/token/generic-tokentxns2?m=normal&contractAddress=0x210e2b878c8e06a4ca52a9d0e93942bfc5950b95&a=&sid=c36547018faca9a52888b6b654edb21a&p=1";
        $snoopy->cookies["ASP.NET_SessionId"] = 'zuxl5bi1vcrmm0dpnjohuasi';
        $snoopy->fetch($url);
        $html = $snoopy->results;
        if($html)
        {
            if ($getPage)
            {
                //总页数
                preg_match('/<li Class="page-item disabled"[^>]*?>(.*?)<\/li>/s', $html, $li);
                if (is_array($li) && isset($li[1]) && $li[1])
                {
                    preg_match_all("/<strong[^>]*?>(.*?)<\/strong>/s", $li[1], $strong);
                    if (is_array($strong) && isset($strong[1]) && is_array($strong[1]) && $strong[1])
                    {
                        if (isset($strong[1][1])) {
                            $arr['totalPage'] = intval($strong[1][1]);
                        }
                    }
                }
            }
            
            //匹配数据
            preg_match("/<tbody[^>]*?>(.*?)<\/tbody>/s", $html, $tbody);
            if (is_array($tbody) && isset($tbody[1]) && $tbody[1])
            {
                $data = [];
                preg_match_all("/<tr[^>]*?>(.*?)<\/tr>/s", $tbody[1], $tr);
                if (is_array($tr) && isset($tr[1]) && is_array($tr[1]) && $tr[1])
                {
                    foreach ($tr[1] as $t)
                    {
                        $tr = [];
                        preg_match_all("/<td[^>]*?>(.*?)<\/td>/s", $t, $td);
                        if (is_array($td) && isset($td[0]) && $td[0])
                        {
                            foreach ($td[0] as $dk=>$d)
                            {
                                $field = strip_tags($d);
                                $field = trim($field);
                                if ($dk==1) {
                                    $tr['address'] = $field;
                                }
                                if ($dk==2) {
                                    $tr['quantity'] = str_replace(",", "", $field);
                                }
                            }
                            $arr['list'][] = $tr;
                        }
                    }
                }
            }
        }
        return $arr;
    }
    
    public function getChainBalance(Request $request)
    {
    
        $in = $request->input();
        $address = $in['address'];
        $contract_address = $in['contract_address'];
        $userModel = new User();
        //查询地址余额
        $balance = $userModel->GetChainBalance($address, $contract_address);
        var_dump($balance);die;
    }
    
    public function getSpacexPrice1(Request $request)
    {
        $in = $request->input();
        
        $price = '0';
        try
        {
            $wbnbContractAddress = env('WBNB_ADDRESS');
            $contract_address = env('SPACEX_ADDRESS_LP');   //SPACEX|WBNB LP合约地址
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address //LP合约地址
                ],
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']) ||
                !isset($result['data']['token0']) || !isset($result['data']['token1'])
                )
            {
                Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败');
            }
            else
            {
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                
                //查询BNB|USDT 价格
                $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                if (bccomp($bnbUsdtPrice, '0', 10)>0)
                {
                    if ($token1==$wbnbContractAddress)
                    {
                        $usdtNum = bcmul($result['data']['reserve1'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve0'], 10);
                    } else {
                        $usdtNum = bcmul($result['data']['reserve0'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve1'], 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        var_dump($price);
    }
    
    public function getSpacexPrice2(Request $request)
    {
        $price = '0';
        $pair_path = [];
        try
        {
            $bnbAddress = env('WBNB_ADDRESS');
            $spacexAddress = env('SPACEX_ADDRESS');
            $url = "https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token={$bnbAddress}&to_token={$spacexAddress}&chain=bsc&max_hops=3&max_routes=6&protocol=v3";
            
            $client = new Client();
            $response = $client->get($url, [
                'timeout' => 10,
                'verify' => false
            ]);
            
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['status']) || $result['status']!=1 ||
                !isset($result['data']) || !is_array($result['data']) || !$result['data'] ||
                !isset($result['data'][0]) || !is_array($result['data'][0]) || !$result['data'][0] ||
                !isset($result['data'][0]['pair_path']) || !is_array($result['data'][0]['pair_path']) || !$result['data'][0]['pair_path'] ||
                !isset($result['data'][0]['pair_path'][0]) || !is_array($result['data'][0]['pair_path'][0]) || !$result['data'][0]['pair_path'][0] ||
                !isset($result['data'][0]['pair_path'][0]['token_in']) || !isset($result['data'][0]['pair_path'][0]['token_out']) ||
                !isset($result['data'][0]['pair_path'][0]['reserve_in']) || !isset($result['data'][0]['pair_path'][0]['reserve_out']) ||
                !$result['data'][0]['pair_path'][0]['token_in'] || !$result['data'][0]['pair_path'][0]['token_out']
                )
            {
                Log::channel('ave_price')->info('查询SPACEX价格失败');
            }
            else
            {
                $addressArr = [
                    $bnbAddress,
                    $spacexAddress
                ];
                $token_in = strtolower($result['data'][0]['pair_path'][0]['token_in']);
                $token_out = strtolower($result['data'][0]['pair_path'][0]['token_out']);
                if (!in_array($token_in, $addressArr) || !in_array($token_out, $addressArr)) {
                    Log::channel('ave_price')->info('查询SPACEX价格失败');
                }
                else
                {
                    $reserve_in = $result['data'][0]['pair_path'][0]['reserve_in'];
                    $reserve_out = $result['data'][0]['pair_path'][0]['reserve_out'];
                    $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                    $pair_path = $result['data'][0]['pair_path'][0];
                    if ($token_in==$bnbAddress) {
                        $usdtNum = bcmul($reserve_in, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    } else {
                        $usdtNum = bcmul($reserve_out, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('ave_price')->info('查询SPACEX价格失败',['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        
        var_dump($price,$url,$pair_path);
    }
    
    //查询钱包充值最新ID
    public function walletRechargeLastId(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        
        $info = Recharge::on('wallet')->orderBy('id', 'desc')->first(['id']);
        if ($info) {
            $data['id'] = $info->id;
        } else {
            $data['id'] = '暂无数据';
        }
        
        echoJson($data);
    }
    
    //钱包充值本地回调
    public function walletRechargeNotify(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        if (!isset($in['id']) || !$in['id'] || intval($in['id'])<=0) {
            exit('访问失败');
        }
        $id = intval($in['id']);
        $info = Recharge::on('wallet')->where('id', $id)->first();
        if ($info)
        {
            $data = $info->toArray();
            $http = new Client();
            $response = $http->post(env('APP_URL').'/api/callback/callback',[
                'form_params' => $data,
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
        }
        echoJson();
    }
    
    //钱包充值本地批量回调
    public function walletRechargeNotifyPage(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $list = Recharge::on('wallet')->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        if ($list) {
            $http = new Client();
            foreach ($list as $val) {
                $response = $http->post(env('APP_URL').'/api/callback/callback',[
                    'form_params' => $val,
                    'timeout' => 10,
                    'verify' => false
                ]);
                //                 $result = $response->getBody()->getContents();
            }
            //             Log::channel('recharge_callback')->info('批量充值回调');
        }
        echoJson();
    }
    
    public function encrypterDecrypt(Request $request)
    {
        $in = $request->input();
        $content = $in['content'];
        
        self::init();
        $content = self::$encrypter->decryptData($content);
        echo $content;die;
    }
}
