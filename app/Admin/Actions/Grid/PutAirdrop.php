<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MainCurrency;
use App\Models\AirdropStage;
use App\Models\AirdropUser;
use App\Models\MyRedis;
use App\Models\User;
use App\Models\BitQuery;
use App\Models\AirdropConfig;
use App\Models\UserVvQuantify;
use App\Models\AirdropRecord;
use App\Models\UserVvQuantifyWait;

class PutAirdrop extends AbstractTool
{
    /**
     * @return string
     */
    protected $title = '空投投放';
    protected $style = 'btn btn-primary';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $lockKey = 'SetAirdropNum';
        $MyRedis = new MyRedis();
        $lock = $MyRedis->setnx_lock($lockKey, 180);
        if ($lock)
        {
            //空投统计
            set_time_limit(0);
            ini_set('memory_limit','2048M');
           
            $AirdropConfig = AirdropConfig::query()->where('id', 1)->first();
            if (bccomp($AirdropConfig->put_total, '0', 6)>0) 
            {
                //VV（量化分红）独立于VV 通过后台手动触发投放，依据个人持有工作态超算值占比均分
                $list = User::query()
                    //                 ->where('is_work', 1)
                    ->where('work_calculation', '>', 0)
                    ->get(['id','work_calculation'])
                    ->toArray();
                if ($list)
                {
                    $ordernum = get_ordernum();
                    $time = time();
                    $datetime = date('Y-m-d H:i:s', $time);
                    
                    $all_calculation = '0';   //全⽹工作态超算值
                    
                    foreach ($list as $val)
                    {
                        if (bccomp($val['work_calculation'], '0', 6)>0)
                        {
                            //计算全⽹工作态超算值
                            $all_calculation = bcadd($all_calculation, $val['work_calculation'], 6);
                        }
                    }
                    
                    if (bccomp($all_calculation, '0', 6)>0)
                    {
                        $userList = [];
                        $totalSub = '0';
                        //加权分配静态奖励
                        foreach ($list as $val)
                        {
                            $vv_quantify = bcmul($AirdropConfig->put_total, bcdiv($val['work_calculation'], $all_calculation, 6), 2);
                            if (bccomp($vv_quantify, '0', 6)>0)
                            {
                                if (!isset($userList[$val['id']]))
                                {
                                    $userList[$val['id']] = [
                                        'user_id' => $val['id'],
                                        'work_calculation' => $val['work_calculation'],
                                        'vv_quantify' => $vv_quantify
                                    ];
                                    $totalSub = bcadd($totalSub, $vv_quantify, 2);
                                }
                            }
                        }
                        
                        if ($userList)
                        {
                            $quantifyData = [];
                            foreach ($userList as $user)
                            {
                                $up = [];
                                if (bccomp($user['vv_quantify'], '0', 2)>0)
                                {
                                    $up['vv_quantify'] = DB::raw("`vv_quantify`+{$user['vv_quantify']}");
                                    //分类1系统增加2系统扣除3余额提币4提币驳回5空投奖励
                                    $quantifyData[] = [
                                        'ordernum' => $ordernum,
                                        'user_id' => $user['user_id'],
                                        'from_user_id' => 0,
                                        'type' => 1,
                                        'cate' => 5,
                                        'total' => $user['vv_quantify'],
                                        'msg' => '空投奖励',
                                        'content' => '空投奖励',
                                        'created_at' => $datetime,
                                        'updated_at' => $datetime,
                                    ];
                                }
                                
                                if ($up) {
                                    User::query()->where('id', $user['user_id'])->update($up);
                                }
                            }
                            
                            if ($quantifyData) {
                                $quantifyData = array_chunk($quantifyData, 1000);
                                foreach ($quantifyData as $ndata) {
                                    UserVvQuantify::query()->insert($ndata);
                                }
                            }
                        }
                        
                        if (bccomp($totalSub, '0', 2)>0) 
                        {
                            AirdropConfig::query()->where('id', 1)->decrement('put_total', $totalSub);
                            $AirdropRecord = new AirdropRecord();
                            $AirdropRecord->type = 2;
                            $AirdropRecord->total = $totalSub;
                            $AirdropRecord->cate = 3;   //分类1系统增加2系统扣除3投放扣除
                            $AirdropRecord->save();
                        }
                    }
                }
            } else {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error('投放数量为0');
            }
            
//             $MyRedis = new MyRedis();
            $MyRedis->del_lock($lockKey);
            return $this
                ->response()
                ->success('操作成功')
                ->refresh();
        } 
        else 
        {
            return $this->response()->error('操作频繁');
        }
    }

    /**
     * @return string|void
     */
    protected function href()
    {
        // return admin_url('auth/users');
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		return ['空投投放', '此操作将空投'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
