<?php

namespace App\Jobs;

use App\Models\DynamicRate;
use App\Models\InvestDestroyLog;
use App\Models\InvestLpLog;
use App\Models\InvestPledgeLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDynamicPowerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userIds;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userIds = [])
    {
        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('task')->info('开始处理任务',$this->userIds);
        if (empty($this->userIds)){
            return;
        }
        $userList = User::query()->whereIn('id',$this->userIds)->get();

        foreach ($userList as $user){
            $dynamicPower = 0;//动态算力
            $dynamicRate = DynamicRate::getDynamicRate($user->total_static_power);
            if (empty($dynamicRate)){
                //更新动态算力
                $user->balance_factor = 0;
                $user->total_dynamic_power = $dynamicPower;
                $user->total_power = bcadd($user->total_dynamic_power,$user->total_static_power,9);
                $user->save();
            }else{
                //更新下平衡系数
                $maxPerformance = User::query()->where('parent_id',$user->id)->orderByDesc('performance')->first();
                if (!empty($maxPerformance)){
                    $otherPerformance = User::query()->where('parent_id',$user->id)->where('id','<>',$maxPerformance->id)->sum('performance');
                    if ($otherPerformance <= 0){
                        $balance_factor = 0;
                        $dynamicPower = 0;
                    }else{
                        $balance_factor = bcdiv($otherPerformance,$maxPerformance->performance);
                        if ($balance_factor >= 1){
                            $balance_factor = 1;
                        }
                        //查找等级
                        //查找所有下级
                        $dynamicRate = explode(',',$dynamicRate);
                        $childrenList = User::query()->where('path','like','%-'.$user->id.'-%')->get();
                        foreach ($childrenList as $children){
                            $path = explode('-',trim($children->path,'-'));
                            $path = array_reverse($path);
                            $position = array_search($user->id,$path);
                            if (isset($dynamicRate[$position])){
                                //判断是不是大区底下的人
                                if (in_array($maxPerformance->id,$path) || $children->id==$maxPerformance->id){
                                    $rate = $balance_factor;
                                }else{
                                    $rate = 1;
                                }
                                Log::channel('task')->info('当前用户'.$children->name.'的平衡系数为'.$rate,$path);

                                $pledgeInvestLog = InvestPledgeLog::query()->where('user_id',$children->id)->where('status',1)->with(['invest'=>function($query){
                                    $query->select(['id','seven_rate','six_rate','five_rate','zero_rate']);
                                }])->select(['id','invest_id','type','before_power'])->get();
                                $pledgeDynamicPower = 0;
                                foreach ($pledgeInvestLog as $item){
                                    if ($item->type==1){
                                        $pledgeDynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->seven_rate/100),$dynamicRate[$position]),$rate);
                                    }elseif ($item->type==2){
                                        $pledgeDynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->six_rate/100),$dynamicRate[$position]),$rate);
                                    }elseif ($item->type==3){
                                        $pledgeDynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->five_rate/100),$dynamicRate[$position]),$rate);
                                    }else{
                                        $pledgeDynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->zero_rate/100),$dynamicRate[$position]),$rate);
                                    }
                                    $dynamicPower = bcadd($pledgeDynamicPower,$dynamicPower);
                                }
                                Log::channel('task')->info('质押动态算力之和为'.$pledgeDynamicPower);


                                $destroyInvestLog = InvestDestroyLog::query()->where('user_id',$children->id)->where('status',1)->with(['invest'=>function($query){
                                    $query->select(['id','dynamic_rate']);
                                }])->select(['id','invest_id','before_power'])->get();
                                $destroydynamicPower = 0;
                                foreach ($destroyInvestLog as $item){
                                    $destroydynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->dynamic_rate/100),$dynamicRate[$position]),$rate);
                                    $dynamicPower = bcadd($dynamicPower,$destroydynamicPower);
                                }
                                Log::channel('task')->info('销毁动态算力之和为'.$destroydynamicPower);


                                $lpInvestLog = InvestLpLog::query()->where('user_id',$children->id)->where('status',1)->with(['invest'=>function($query){
                                    $query->select(['id','dynamic_rate']);
                                }])->select(['id','invest_id','before_power'])->get();
                                $lPdynamicPower = 0;
                                foreach ($lpInvestLog as $item){
                                    $lPdynamicPower = bcmul(bcmul(bcmul($item->before_power,$item->invest->dynamic_rate/100),$dynamicRate[$position]),$rate);
                                    $dynamicPower = bcadd($dynamicPower,$lPdynamicPower);
                                }
                                Log::channel('task')->info('LP动态算力之和为'.$lPdynamicPower);

                            }
                        }
                    }
                }else{
                    $balance_factor = 0;
                    $dynamicPower = 0;
                }

                //更新动态算力
                $user->balance_factor = $balance_factor;
                $user->total_dynamic_power = $dynamicPower;
                $user->total_power = bcadd($user->total_dynamic_power,$user->total_static_power,9);
                $user->save();
            }
        }
    }
}
