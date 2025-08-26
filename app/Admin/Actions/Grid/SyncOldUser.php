<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\MyRedis;
use App\Models\OldUserDatum;
use App\Models\UserMachine;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SyncOldUser extends AbstractTool
{
    /**
     * @return string
     */
    protected $title = '同步数据';
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
        $lockKey = 'forms:SyncOldUser';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 600);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
//         TRUNCATE old_user_data;
//         TRUNCATE user_usdt;
//         TRUNCATE user_machine;
//         update users set usdt=0,machine_win_total=0,machine_cash_usdt=0;
        
        $datetime = date('Y-m-d H:i:s');
        $list = OldUserDatum::query()
//             ->where('user_id', '>', 0)
            ->where('is_sync', '=', 0)
            ->get()
            ->toArray();
        if ($list) 
        {
            $machineDayRate = @bcadd(config('machine_day_rate'), '0', 6);
            $newData = $userList = $logIds = [];
            //每天早上8点释放
            $release_time = date('Y-m-d 08:00:00', time()+86400);
            
            foreach ($list as $val) 
            {
                $user_id = $val['user_id'];
                if ($val['user_id']<=0) {
                    $user = User::query()->where('wallet', $val['new_wallet'])->first(['id','wallet']);
                    $user_id = $user ? $user->id : 0;
                    if ($user_id>0) {
                        OldUserDatum::query()->where('id', $val['id'])->update(['user_id'=>$user_id]);
                    }
                } 
                
                if ($user_id>0) 
                {
                    $logIds[] = $val['id'];
                    
                    if (!isset($userList[$user_id])) {
                        $userList[$user_id] = [
                            'user_id' => $user_id,
                            'is_effective' => 1,    //有效账户0否1是
                            'usdt' => '0',                  //USDT | 已释放未提现数量(U)
                            'machine_win_total' => '0',    //累计中奖矿机金额
                            'machine_cash_usdt' => '0',    //累计矿机提现总额
                            //合成一台矿机
                            'total' => '0',
                            'residue_total' => '0',
                        ];
                    }
                    $userList[$user_id]['usdt'] = bcadd($userList[$user_id]['usdt'], $val['wait_cash_usdt'], 6);
                    $userList[$user_id]['machine_win_total'] = bcadd($userList[$user_id]['machine_win_total'], $val['machine_total'], 6);
                    $userList[$user_id]['machine_cash_usdt'] = bcadd($userList[$user_id]['machine_cash_usdt'], $val['over_cash_usdt'], 6);
                    //合成一台矿机
                    $userList[$user_id]['total'] = bcadd($userList[$user_id]['total'], $val['machine_total'], 6);
                    $userList[$user_id]['residue_total'] = bcadd($userList[$user_id]['residue_total'], $val['machine_residue_total'], 6);
                }
            }
            
            if ($logIds)
            {
                $logIds = array_chunk($logIds, 400);
                foreach ($logIds as $data) {
                    OldUserDatum::query()->whereIn('id', $data)->update(['is_sync'=>1]);
                }
            }
            
            if ($userList)
            {
                foreach ($userList as $user) 
                {
                    $nUp = [];
                    if (bccomp($user['usdt'], '0', 6)>0) {
                        $nUp['usdt'] = DB::raw("`usdt`+{$user['usdt']}");
                    }
                    if (bccomp($user['machine_win_total'], '0', 6)>0) {
                        $nUp['machine_win_total'] = DB::raw("`machine_win_total`+{$user['machine_win_total']}");
                    }
                    if (bccomp($user['machine_cash_usdt'], '0', 6)>0) {
                        $nUp['machine_cash_usdt'] = DB::raw("`machine_cash_usdt`+{$user['machine_cash_usdt']}");
                    }
                    if ($nUp) {
                        User::query()->where('id', $user['user_id'])->update($nUp);
                    }
                    
                    if (bccomp($user['total'], '0', 6)>0)
                    {
                        $newData[] = [
                            'ordernum' => get_ordernum(),
                            'user_id' => $user['user_id'],
                            'total' => $user['total'],
                            'residue_total' => $user['residue_total'],
                            'rate' => $machineDayRate,
                            'source' => 2,  //来源1互助拼团2后台导入
                            'release_time' => $release_time,
                            'created_at' => $datetime,
                            'updated_at' => $datetime,
                        ];
                    }
                }
            }
            
            if ($newData)
            {
                $newData = array_chunk($newData, 400);
                foreach ($newData as $data) {
                    UserMachine::query()->insert($data);
                }
            }
            
            $MyRedis->del_lock($lockKey);
        } 
        else 
        {
            $MyRedis->del_lock($lockKey);
        }
        
        
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
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
		return ['确定同步数据?', '此操作将同步历史用户数据'];
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
