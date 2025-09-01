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

class NftController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        
        $list = NftConfig::query()
            ->get([
                'lv','name','name_en','status','price','upgrade_type','upgrade_value','next_lv',
                'fee_rate','profit_rate','gas_add_rate','stock','image','desc','desc_en'
            ])
            ->toArray();
        if ($list) 
        {
            $lang = getLang();
            $nameField = 'name'.$lang;
            $descField = 'desc'.$lang;
            
            foreach ($list as &$val) 
            {
                $val['image'] = getImageUrl($val['image']);
                $val['stock'] = $val['stock']<=0 ? 0 : $val['stock'];
                
                $fee_rate = $val['fee_rate']*100;
                $val['fee_rate'] = $fee_rate.'%';
                
                $profit_rate = $val['profit_rate']*100;
                $val['profit_rate'] = $profit_rate.'%';
                
                $gas_add_rate = $val['gas_add_rate']*100;
                $val['gas_add_rate'] = $gas_add_rate.'%';
                
                $val['name'] = isset($val[$nameField]) ? $val[$nameField] : '';
                $val['desc'] = isset($val[$descField]) ? $val[$descField] : '';
                unset($val['name_en'],$val['desc_en']);
            }
        }
        
        return responseJson($list);
    }
    
    /**
     * 购买NFT
     */
    public function buy(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $lv = 1;
        if (isset($in['lv']) && in_array($in['lv'], [1,2,3,4,5,6])) {
            $lv = intval($in['lv']);
        }
        
        $pay_type = 1;  //支付类型1USDT(链上)
        
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
            $NftConfig = NftConfig::query()->where('lv', $lv)->first();
            
            if (!$NftConfig) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            if ($NftConfig->status<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.敬请期待'));
            }
            if ($NftConfig->price<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            if ($NftConfig->stock<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.库存不足'));
            }
            
            $price = $NftConfig->price;
            
            $ordernum = get_ordernum();
            
            $order = new NftOrderLog();
            $order->ordernum = $ordernum;
            $order->user_id = $user->id;
            $order->lv = $NftConfig->lv;
            $order->price = $NftConfig->price;
            $order->pay_type = $pay_type;
            $order->save();
            
            $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
            
            $OrderLog = new OrderLog();
            $OrderLog->ordernum = $ordernum;
            $OrderLog->user_id = $user->id;
            $OrderLog->type = 3;    //订单类型1余额提币2注册订单3购买NFT
            $OrderLog->save();
          
            DB::commit();
            $MyRedis->del_lock($lockKey);
            
            
            $pay_data = [];
            
            $tmp = [];
            $tmp[] = [
                'num' => $NftConfig->price,
            ];
            $pay_data[] = [
                'total' => $NftConfig->price,
                'contract_address' => $usdtCurrency->contract_address,
                'list' => $tmp
            ];
            
            $data = [
                'remarks' => $ordernum,
                'pay_data' => $pay_data
            ];
            
            return responseJson($data);
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
    
    public function buyLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = NftOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        
        if ($list) {
            foreach ($list as &$val) {
                $val['name'] = getNftName($val['lv']);
            }
        }
            
        return responseJson($list);
    }
    
    public function myNft(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $list = UserNftStat::query()
            ->where('user_id', $user->id)
            ->where('num', '>', 0)
            ->orderBy('lv', 'asc')
            ->get(['lv','num'])
            ->toArray();
        if ($list) 
        {
            $NftConfig = NftConfig::GetListCache();
            $NftConfig = array_column($NftConfig, null, 'lv');
            
            foreach ($list as &$val) 
            {
                $conf = $NftConfig[$val['lv']];
                $val['name'] = getNftName($val['lv'], $NftConfig);
                $val['image'] = getImageUrl($conf['image']);
            }
        }
            
        return responseJson($list);
    }
    
    /**
     * 合成升级NFT
     */
    public function upgrade(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $lv = 1;
        if (isset($in['lv']) && in_array($in['lv'], [1,2,3,4,5,6])) {
            $lv = intval($in['lv']);
        }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 15);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        DB::beginTransaction();
        try
        {
            $NftConfig = NftConfig::query()->where('lv', $lv)->first();
            
            if (!$NftConfig) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.系统维护'));
            }
            if ($NftConfig->upgrade_type!=2 || $NftConfig->next_lv==0 || $NftConfig->upgrade_value<=0) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.此NFT不可合成升级'));
            }
            
            $UserNftStat = UserNftStat::query()
                ->where('user_id', $user->id)
                ->where('lv', $lv)
                ->first();
            if (!$UserNftStat || $NftConfig->upgrade_value>$UserNftStat->num) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.NFT数量不足'));
            }
            
            $nextNft = NftConfig::query()->where('lv', $NftConfig->next_lv)->first();
            if (!$nextNft) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.此NFT不可合成升级'));
            }
            
            $user = User::query()->where('id', $user->id)->first(['id','nft_rank']);
            
            if ($nextNft->lv>$user->nft_rank) {
                $user->nft_rank = $nextNft->lv;
                $user->save();
            }
            
            $ordernum = get_ordernum();
            
            $userModel = new User();
            //统计&&日志
            //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得6合成扣除7签到扣除
            $map = ['cate'=>6, 'msg'=>'合成扣除', 'ordernum'=>$ordernum];
            $userModel->handleNftLog($user->id, $NftConfig->upgrade_value, $NftConfig->lv, 2, $map);
            
            $map = ['cate'=>2, 'msg'=>'合成获得', 'ordernum'=>$ordernum];
            $userModel->handleNftLog($user->id, 1, $nextNft->lv, 1, $map);
            
            $total_day = $nextNft->upgrade_type==1 ? $nextNft->upgrade_value : 0;
            $UserNft = new UserNft();
            $UserNft->user_id = $user->id;
            $UserNft->target_id = 0;
            $UserNft->source_type = 2;  //来源1注册赠送2合成获得3签到获得4平台购买5推荐获得
            $UserNft->lv = $nextNft->lv;
            $UserNft->status = 1;       //状态1仓库中2已合成3已升级
            $UserNft->upgrade_type = $nextNft->upgrade_type;
            $UserNft->total_day = $total_day;
            $UserNft->wait_day = $total_day;
            $UserNft->save();
            
            UserNft::query()
                ->where('user_id', $user->id)
                ->where('lv', $NftConfig->lv)
                ->where('status', 1)   //状态1仓库中2已合成3已升级
                ->orderBy('id', 'asc')
                ->limit($NftConfig->upgrade_value)
                ->update([
                    'status' => 2,
                    'target_id' => $UserNft->id
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
    
    public function nftLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        $list = UserNftLog::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        
        if ($list)
        {
//             $NftConfig = NftConfig::GetListCache();
//             $NftConfig = array_column($NftConfig, null, 'lv');
            
            foreach ($list as &$val)
            {
                $val['name'] = getNftName($val['lv']);
                $val['content'] = $val['msg'] = __("error.NFT类型{$v['cate']}");
//                 $val['image'] = getImageUrl($conf['image']);
            }
        }
            
        return responseJson($list);
    }
}
