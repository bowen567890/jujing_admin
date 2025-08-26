<?php

namespace App\Models;

use App\Jobs\test;
use App\Jobs\UpdateDynamicPowerJob;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Dcat\Admin\Traits\ModelTree;
use GuzzleHttp\Client;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable ,HasDateTimeFormatter, ModelTree;

    protected $titleColumn = 'name';

    protected $parentColumn = 'parent_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet',
        'path',
        'code',
        'parent_id',
        'level',
        'headimgurl',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            //生成邀请码
            while (true){
                $code = getRandStr(6);
                if (!self::query()->where('code',$code)->exists()){
                    break;
                }
            }
            $model->code = $code;
        });
            
        static::created(function ($model){
            
            if ($model->path) 
            {
                //更新直推 团队
                $pUser = explode('-',trim($model->path,'-'));
                $pUserId = $pUser[count($pUser)-1];
                //给上级直推人数加1 ，以及整个链条上的所有人团队人数+1
                self::query()->where('id',$pUserId)->increment('zhi_num');
                self::query()->whereIn('id',$pUser)->increment('group_num');
                //自动获取地址
                //$model->wallet = getWallet($model->id);
                $model->save();
            }
        });
    }
    
    public function group(){
        return $this->hasOne(LevelConfig::class,'id','level_id');
    }

    public function parent(){
        return $this->hasOne(self::class,'id','parent_id');
    }

    public function getJWTIdentifier()
    {
        // TODO: Implement getJWTIdentifier() method.
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // TODO: Implement getJWTCustomClaims() method.
        return [];
    }

    public static function upgrade($user,$amount){
        $yAmount = $amount;
        //查看当前level金额
        $nowAmount = LevelConfig::query()->where('id',$user->level_id)->value('amount');
        if ($nowAmount > 0){
            $level = LevelConfig::query()->where('amount',$amount+$nowAmount)->first();
        }else{
            $level = LevelConfig::query()->where('amount',$amount)->first();
        }

        //动态收益需要30%进入黑洞，  70%用来分配
        $blackHole = bcmul($amount,0.3,8);
        //进入黑洞
        BlackHole::insertLog($user->id,$blackHole);

        //实际用来分配的金额
        $amount = bcsub($amount,$blackHole,8);

        if (!empty($user->path)){
            $parentIds = explode('-',trim($user->path,'-'));
            $parentIds = array_reverse($parentIds);
            $levelConfigStatic = LevelConfig::query()->pluck('zhi_rate','id')->toArray();

            foreach ($parentIds as $k=>$userId){
                $parent = User::query()->where('id',$userId)->first();
                //看下这个人什么等级
                $zhi_rate = $levelConfigStatic[$parent->level_id];
                if (empty($zhi_rate)){
                    continue;
                }
                $zhi_rate = explode(',',$zhi_rate);
                //给上级返佣
                if (isset($zhi_rate[$k])){
                    IncomeLog::insertLog($parent,bcmul($amount,$zhi_rate[$k],8),1,'usdt');
                }
            }
        }
        //设置用户等级
        $user->level_id = $level->id;
        if ($user->max_div > 0){
            $user->fu_tou += $yAmount;
        }
        $user->max_div = bcadd($user->has_div,$yAmount * 3,8);
        $user->save();
        UsersPower::query()->where('user_id',$user->id)->where('type',4)->increment('power',$yAmount);
        //自己升级了更新下动态算力

        $jinId = [$user->id];
        if (!empty($user->path)){
            $parentIds = explode('-',trim($user->path,'-'));
            $parentIds = array_reverse($parentIds);
            $jinId = array_merge($jinId,$parentIds);
        }
        UpdateDynamicPowerJob::dispatch($jinId);
    }
    
    
    /**
     * @param unknown $total 金额
     * @param unknown $num   单数
     * @param number $type
     */
    public function handleTeamYeji($path, $total=0, $num=1, $type=1)
    {
        $parentIds = explode('-',trim($path,'-'));
        $parentIds = array_reverse($parentIds);
        $parentIds = array_filter($parentIds);
        if ($parentIds) {
            if ($type==1) {
                User::query()->whereIn('id', $parentIds)->update([
                    'team_yeji'=>DB::raw("`team_yeji`+{$total}"),
                    'total_yeji'=>DB::raw("`total_yeji`+{$total}"),
                    'team_num'=>DB::raw("`team_num`+{$num}"),
                    'total_num'=>DB::raw("`total_num`+{$num}"),
                ]);
            } else {
                User::query()->whereIn('id', $parentIds)->update([
                    'team_yeji'=>DB::raw("`team_yeji`-{$total}"),
                    'total_yeji'=>DB::raw("`total_yeji`-{$total}"),
                    'team_num'=>DB::raw("`team_num`-{$num}"),
                    'total_num'=>DB::raw("`total_num`-{$num}"),
                ]);
            }
        }
    }
    
    /**
     * @param unknown $total 金额
     * @param unknown $num   单数
     * @param number $type
     */
    public function handleSelfYeji($user_id, $total=0, $num=1, $type=1)
    {
        if ($type==1) {
            User::query()->where('id',$user_id)->update([
                'self_yeji'=>DB::raw("`self_yeji`+{$total}"),
                'total_yeji'=>DB::raw("`total_yeji`+{$total}"),
                'self_num'=>DB::raw("`self_num`+{$num}"),
                'total_num'=>DB::raw("`total_num`+{$num}"),
            ]);
        } else {
            User::query()->where('id',$user_id)->update([
                'self_yeji'=>DB::raw("`self_yeji`-{$total}"),
                'total_yeji'=>DB::raw("`total_yeji`-{$total}"),
                'self_num'=>DB::raw("`self_num`-{$num}"),
                'total_num'=>DB::raw("`total_num`-{$num}"),
            ]);
        }
    }
    

    
    /**
     * 处理余额,
     */
    public function handleUser($table, $user_id, $total, $type, $map = array())
    {
        if (!in_array($table, array('usdt'))) {
            return false;
        }
        $model = DB::table("user_{$table}");
        $user = Db::table('users');
        if (!is_numeric($total)) {
            return false;
        }
        if (!in_array($type, array(1, 2))) {
            return false;
        }
        $r = null;
        $total = @bcadd($total, 0, 6);
        if ($type == 1) {
            $r = $user->where(array('id' => $user_id))->increment($table, $total);
        } else if ($type == 2) {
            $r = $user->where(array('id' => $user_id))->decrement($table, $total);
        }
        
        if (isset($map['date']) && $map['date']) {
            $date = $map['date'];
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $add = array(
            'user_id' => $user_id,
            'type' => $type,
            'total' => $total,
            'ordernum' => isset($map['ordernum']) && $map['ordernum'] ? $map['ordernum'] : '',
            'cate' => isset($map['cate']) ? $map['cate'] : 0,
            'msg' => isset($map['msg']) ? $map['msg'] : '',
            'created_at' => $date,
            'updated_at' => $date,
        );
        
        if (isset($map['content']) && $map['content']) {
            $add['content'] = $map['content'];
        } else {
            $add['content'] = $add['msg'];
        }
        if (isset($map['from_user_id'])) {
            $add['from_user_id'] = $map['from_user_id'];
        }
        
        $addid = $model->insertGetId($add);
        if (($r || $r === 0) && $addid) {
            return true;
        } else {
            return false;
        }
    }
    
    //同步BSC持币用户余额
    public function SyncBalance()
    {
        $currencyList = MainCurrency::query()->pluck('contract_address','id')->toArray();
//         $contractAddress = MainCurrency::query()->where('id', 3)->value('contract_address');
        //         $contractAddress = env('USDT_ADDRESS');
        //             set_time_limit(0);
        //             ini_set('memory_limit','2048M');
        //只查有效的用户 NFT>0
        //         User::select(['id','wallet','hold_sats','hold_sats_low'])
        //         ->chunk(500, function ($walletList) use ($contractAddress, $apiKey)
        //         {
        //             $list = $walletList->toArray();
        //             if ($list)
        //             {
        
            //             }
            //         });
            
            $list = User::query()
                ->where('is_del', '=', 0)
                ->where('wallet', '!=', '')
                ->get(['id','wallet','contribution_entt','contribution_dino', 'hold_entt', 'hold_dino', 'total_hold_entt', 'total_hold_dino'])
                ->toArray();
            if ($list)
            {
                $enttContractAddress = $currencyList[2];
                $dinoContractAddress = $currencyList[3];
                
                $client = new Client();
                foreach ($list as $val)
                {
                    $up = [];
                    //查询ENTT持币分红地址余额
                    $enttData = [
                        'contract_address' => $enttContractAddress, //代币合约地址
                        'address' => $val['wallet'], //用户地址
                    ];
                    $dinoData = [
                        'contract_address' => $dinoContractAddress, //代币合约地址
                        'address' => $val['wallet'], //用户地址
                    ];
                    try
                    {
                        $response = $client->post('http://127.0.0.1:9090/v1/bnb/balanceOf',[
                            'form_params' => $enttData,
                            'timeout' => 10,
                            'verify' => false
                        ]);
                        
                        $result = $response->getBody()->getContents();
                        if (!is_array($result)) {
                            $result = json_decode($result, true);
                        }
                        if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 || !isset($result['data']) || !isset($result['data']['balance']))
                        {
//                             continue;
                        }
                        else
                        {
                            $balance = $result['data']['balance'];
                            if ($balance>0) 
                            {
                                $balance = $balance/pow(10,18);
                                $balance = @bcadd($balance, '0', 6);
                                if (bccomp($balance, '0', 6)>0)
                                {
                                    $up['hold_entt'] = $balance;
                                    $up['total_hold_entt'] = bcadd($val['contribution_entt'], $balance, 6);
//                                     $up['total_hold_entt'] = DB::raw("`contribution_entt`+{$balance}");
                                } else {
                                    $up['hold_entt'] = 0;
                                    $up['total_hold_entt'] = $val['contribution_entt'];
                                }
                            } else {
                                $up['hold_entt'] = 0;
                                $up['total_hold_entt'] = $val['contribution_entt'];
                            }
                        }
                        
                        //查询DINO余额
                        $response = $client->post('http://127.0.0.1:9090/v1/bnb/balanceOf',[
                            'form_params' => $dinoData,
                            'timeout' => 10,
                            'verify' => false
                        ]);
                        
                        $result = $response->getBody()->getContents();
                        if (!is_array($result)) {
                            $result = json_decode($result, true);
                        }
                        if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 || !isset($result['data']) || !isset($result['data']['balance']))
                        {
                            //                             continue;
                        }
                        else
                        {
                            $balance = $result['data']['balance'];
                            if ($balance>0)
                            {
                                $balance = $balance/pow(10,18);
                                $balance = @bcadd($balance, '0', 6);
                                if (bccomp($balance, '0', 6)>0)
                                {
                                    $up['hold_dino'] = $balance;
                                    $up['total_hold_dino'] = bcadd($val['contribution_dino'], $balance, 6);
                                    //                                     $up['total_hold_dino'] = DB::raw("`contribution_dino`+{$balance}");
                                } else {
                                    $up['hold_dino'] = 0;
                                    $up['total_hold_dino'] = $val['contribution_dino'];
                                }
                            } else {
                                $up['hold_dino'] = 0;
                                $up['total_hold_dino'] = $val['contribution_dino'];
                            }
                        }
                        
                        if ($up) 
                        {
                            User::query()->where('id', $val['id'])->update($up);
                        }
                    }
                    catch (\Exception $e)
                    {
                        continue;
                    }
                }
            }
        }
        /**
         * 查询地址余额
         * $address             查询地址
         * $contract_address    代币合约地址
         */
        public function GetChainBalance($address='', $contract_address='')
        {
            $balance = '0';
            if ($address && $contract_address) 
            {
                //查询ENTT持币分红地址余额
                $postData = [
                    'contract_address' => $contract_address,    //代币合约地址
                    'address' => $address                       //用户地址
                ];
                
                $client = new Client();
                try
                {
                    $response = $client->post('http://127.0.0.1:9090/v1/bnb/balanceOf',[
                        'form_params' => $postData,
                        'timeout' => 10,
                        'verify' => false
                    ]);
                    
                    $result = $response->getBody()->getContents();
                    if (!is_array($result)) {
                        $result = json_decode($result, true);
                    }
                    if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 || !isset($result['data']) || !isset($result['data']['balance']))
                    {
                        Log::channel('chain_balance')->info('查询余额失败');
                    }
                    else
                    {
                        $balance = $result['data']['balance'];
                        if ($balance>0)
                        {
                            $balance = $balance/pow(10,18);
                            $balance = @bcadd($balance, '0', 6);
                            if (bccomp($balance, '0', 6)<=0) {
                                Log::channel('chain_balance')->info('查询余额为零', $result);
                            }
                        } else {
                            Log::channel('chain_balance')->info('查询余额为零', $result);
                        }
                    }
                }
                catch (\Exception $e)
                {
                    Log::channel('chain_balance')->info('查询余额失败');
                }
            }
            return $balance;
        }
        
        /**
         * 更新用户质押登记
         */
        public static function UpdateUserRank($path, $rankConf=[])
        {
            $parentIds = explode('-',trim($path,'-'));
            $parentIds = array_filter($parentIds);
            if ($parentIds)
            {
                if (!$rankConf) {
                    $rankConf = RankConfig::GetListCache();
                    $rankConf = array_column($rankConf, null, 'lv');
                }
                //等级升级
                $parentList = User::query()
                    ->whereIn('id', $parentIds)
                    ->orderBy('level', 'desc')
                    ->get(['id','rank','hold_rank','small_num'])
                    ->toArray();
                if ($parentList && $rankConf)
                {
                    foreach ($parentList as $puser)
                    {
                        $rank = 0;
                        foreach ($rankConf as $val)
                        {
                            //判断小区单数业绩
                            if ($val['small_num']>$puser['small_num']) {
                                continue;
                            }
                            
                            $rank = $val['lv'];
                        }
                        if ($rank!=$puser['rank'])
                        {
                            if ($puser['hold_rank']==0)
                            {
                                self::query()->where('id', $puser['id'])->update(['rank'=>$rank]);
                            }
                            else
                            {
                                if ($rank>$puser['rank']) {
                                    self::query()->where('id', $puser['id'])->update(['rank'=>$rank]);
                                }
                            }
                        }
                    }
                }
            }
        }
}
