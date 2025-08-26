<?php


namespace App\Logic;



use App\Jobs\UpdateDynamicPowerJob;
use App\Models\BlackHole;
use App\Models\InvestDestroy;
use App\Models\InvestDestroyLog;
use App\Models\InvestLp;
use App\Models\InvestLpLog;
use App\Models\InvestPledge;
use App\Models\InvestPledgeLog;
use App\Models\MainCurrency;
use App\Models\Recharge;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RechargeLogic
{

    /**
     * 检测需要调用什么方法
     * @param $data
     */
    public function checkMethod($data){
        if (strpos($data['remarks'],'pledge')!==false){
            $this->pledgeHandle($data);
        }elseif(strpos($data['remarks'],'destroy_package')!==false) {
            $this->destroyPackage($data);
        }elseif(strpos($data['remarks'],'destroy')!==false) {
            $this->destroyHandle($data);
        }elseif(strpos($data['remarks'],'lp')!==false) {
            $this->lpHandle($data);
        }
    }

    /**
     * 解析参数
     * @param $remarks
     * @return false|string[]
     */
    private function parseRemark($remarks){
        return explode('@',$remarks);
    }




    /**
     * 主流币质押  pledge@方案ID@百分比类型@主流币数量 质押挖矿必传 1,2,3,4
     * @param $data
     */
    private function pledgeHandle($data){
        Log::channel('recharge_callback')->info('开始处理质押请求');
        $remarks = $this->parseRemark($data['remarks']);
        //方案ID
        $invest = InvestPledge::query()->where('id',$remarks[1])->first();
        if (empty($invest)){
            Log::channel('recharge_callback')->info('无法找到方案');
            exit;
        }
        $result = MainCurrency::getPledgePowerByInvest($invest,$remarks[3],$remarks[2]);
        $power = $result['power'];
        $bhbNum = $result['bhbNum'];
        $acPower = $result['acPower'];

        DB::beginTransaction();
        try {
            $user = User::query()->where('name',$data['toAddress'])->first();
            $mainCoin = $invest->mainCoinDb;
            $otherCoin = $invest->otherCoinDb;

            InvestPledgeLog::query()->insert([
                'user_id' => $user->id,
                'invest_id' => $invest->id,
                'type' => $remarks[2],
                'main_coin' => $mainCoin->name,
                'main_coin_num' => $remarks[3],
                'main_coin_has' => bcsub($remarks[3],bcmul($remarks[3],config('pledge_main_fee')/100)),
                'main_coin_rate' => $mainCoin->rate,
                'other_coin' => $otherCoin->name,
                'other_coin_num' => $bhbNum,
                'other_coin_rate' => $otherCoin->rate,
                'before_power' => $power,
                'power' => $acPower,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            Recharge::query()->insert([
                'user_id' => $user->id,
                'type' => 1,
                'order_no' =>'R'.date('YmdHis').mt_rand(10000,99999),
                'coin' =>  strtoupper($data['coinToken']),
                'other_coin' => '',
                'nums' => $data['amount'],
                'hash' => $data['hash'],
                'status' => 2,
                'finish_time' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            //给用户新增算力
            $user->master_power = bcadd($user->master_power,$acPower);
            $user->total_static_power = bcadd($user->total_static_power,$acPower);
            $user->save();

            //增加方案购买量
            $invest->total = bcadd($invest->total,$power);
            $invest->save();

            //给上级新增动态算力,自己的算力也有变更,需要及时调整
            $parentId = [];
            if (!empty($user->path)){
                $parentId = array_reverse(explode('-',trim($user->path,'-')));
            }
            $parentId[] = $user->id;
            //给所有上级加业绩
            User::query()->whereIn('id',$parentId)->increment('performance',$acPower);
            Db::commit();
            UpdateDynamicPowerJob::dispatch($parentId);
            Log::channel('recharge_callback')->info('处理质押请求完成');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::channel('recharge_callback')->info('处理质押请求失败' . $e->getMessage() . $e->getLine());
        }
    }



    /**
     * 销毁质押  destroy@方案ID@质押数量
     * @param $data
     */
    private function destroyHandle($data){
        Log::channel('recharge_callback')->info('开始处理销毁请求');
        $remarks = $this->parseRemark($data['remarks']);
        //方案ID
        $invest = InvestDestroy::query()->where('id',$remarks[1])->first();
        if (empty($invest)){
            Log::channel('recharge_callback')->info('无法找到方案');
            exit;
        }
        $result = MainCurrency::getDestroyPowerByInvest($invest,$remarks[2]);
        $power = $result['power'];
        $acPower = $result['acPower'];

        DB::beginTransaction();
        try {
            $user = User::query()->where('name',$data['toAddress'])->first();
            $coin = $invest->coin;

            InvestDestroyLog::query()->insert([
                'user_id' => $user->id,
                'invest_id' => $invest->id,
                'coin' => $coin->name,
                'coin_num' => $remarks[2],
                'coin_exchange_rate' => $coin->rate,
                'before_power' => $power,
                'power' => $acPower,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            Recharge::query()->insert([
                'user_id' => $user->id,
                'type' => 2,
                'order_no' =>'R'.date('YmdHis').mt_rand(10000,99999),
                'coin' =>  strtoupper($data['coinToken']),
                'nums' => $data['amount'],
                'hash' => $data['hash'],
                'status' => 2,
                'finish_time' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            //给用户新增算力
            $user->destroy_power = bcadd($user->destroy_power,$acPower);
            $user->total_static_power = bcadd($user->total_static_power,$acPower);
            $user->save();

            //增加方案购买量
            $invest->total = bcadd($invest->total,$power);
            $invest->save();

            //给上级新增动态算力,自己的算力也有变更,需要及时调整
            $parentId = [];
            if (!empty($user->path)){
                $parentId = array_reverse(explode('-',trim($user->path,'-')));
                $parentId[] = $user->id;
            }
            //给所有上级加业绩
            User::query()->whereIn('id',$parentId)->increment('performance',bcmul($power,$invest->dynamic_rate/100));
            BlackHole::insertLog($user->id,$remarks[2],'SCC');

            Db::commit();
            UpdateDynamicPowerJob::dispatch($parentId);

            Log::channel('recharge_callback')->info('处理销毁请求完成');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::channel('recharge_callback')->info('处理销毁请求失败' . $e->getMessage() . $e->getLine());
        }
    }


    /**
     * LP  lp@方案ID@质押数量
     * @param $data
     */
    private function lpHandle($data){
        Log::channel('recharge_callback')->info('开始处理LP请求');
        $remarks = $this->parseRemark($data['remarks']);
        //方案ID
        $invest = InvestLp::query()->where('id',$remarks[1])->first();
        if (empty($invest)){
            Log::channel('recharge_callback')->info('无法找到方案');
            exit;
        }
        //查询LP算力
        $client = new Client();
        $response = $client->post('http://127.0.0.1:9090/api/wallet/pro/getSwapInfo',[
            'form_params' => [
                'mainChain' => 'BNB',
                'contractAddress' => MainCurrency::query()->where('name','LP')->value('contract_address'),
            ]
        ]);
        $lpResponse = json_decode($response->getBody()->getContents(),true);
        if (empty($lpResponse) || !isset($lpResponse['obj']['totalSupply']) || !isset($lpResponse['obj']['reserve1'])){
            Log::channel('recharge_callback')->info('未查询到LP算力，无法继续操作,结果为',$lpResponse);
            exit;
        }
        Log::channel('recharge_callback')->info('lp算力为',$lpResponse);

        DB::beginTransaction();
        try {
            $user = User::query()->where('name',$data['toAddress'])->first();
            $bhbPrice = MainCurrency::query()->where('name','LP')->value('rate');

            $a= number_format($lpResponse['obj']['reserve1']/$lpResponse['obj']['totalSupply'], 9, '.', '');
            $b = bcmul($remarks[2],2,9);

            $power = bcdiv(bcmul($b,$a,9),$bhbPrice,9);
            $acPower = bcmul($power,$invest->rate/100,9);

            InvestLpLog::query()->insert([
                'user_id' => $user->id,
                'invest_id' => $invest->id,
                'main_coin' => 'LP',
                'lp_num' => $remarks[2],
                'before_power' => $power,
                'power' => $acPower,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            Recharge::query()->insert([
                'user_id' => $user->id,
                'type' => 3,
                'order_no' =>'R'.date('YmdHis').mt_rand(10000,99999),
                'coin' =>  'LP',
                'nums' => $data['amount'],
                'hash' => $data['hash'],
                'status' => 2,
                'finish_time' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            //给用户新增算力
            $user->lp_power = bcadd($user->lp_power,$acPower);
            $user->total_static_power = bcadd($user->total_static_power,$acPower);
            $user->save();

            //增加方案购买量
            $invest->total = bcadd($invest->total,$power);
            $invest->save();

            //给上级新增动态算力,自己的算力也有变更,需要及时调整
            $parentId = [];
            if (!empty($user->path)){
                $parentId = array_reverse(explode('-',trim($user->path,'-')));
            }
            $parentId[] = $user->id;
            //给所有上级加业绩
            User::query()->whereIn('id',$parentId)->increment('performance',$acPower);
            Db::commit();

            UpdateDynamicPowerJob::dispatch($parentId);
            Log::channel('recharge_callback')->info('处理LP请求完成');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::channel('recharge_callback')->info('处理LP请求失败' . $e->getMessage() . $e->getLine());
        }
    }



    public function destroyPackage($data){
        Log::channel('recharge_callback')->info('开始处理销毁包请求');
        $remarks = $this->parseRemark($data['remarks']);
        DB::beginTransaction();
        try {
            $user = User::query()->where('name',$data['toAddress'])->first();

            $user->max_div = bcadd($user->max_div,$remarks[1]*3);
            $user->save();

            Recharge::query()->insert([
                'user_id' => $user->id,
                'type' => 4,
                'order_no' =>'R'.date('YmdHis').mt_rand(10000,99999),
                'coin' =>  'SCC',
                'nums' => $data['amount'],
                'hash' => $data['hash'],
                'status' => 2,
                'finish_time' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            BlackHole::insertLog($user->id,$data['amount'],'SCC');

            Db::commit();
            Log::channel('recharge_callback')->info('处理销毁包请求完成');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::channel('recharge_callback')->info('处理销毁包请求失败' . $e->getMessage() . $e->getLine());
        }
    }

}
