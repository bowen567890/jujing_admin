<?php

namespace App\Admin\Imports;

use App\Models\UserMachine;
use App\Models\OldUserDatum;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class BonusExcelImport implements ToModel, WithStartRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // 0代表的是第一列 以此类推
        // $row 是每一行的数据
        //查询是否存在，存在就不写入
        
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $release_time = date('Y-m-d H:i:s', $time+86400);   //24小时制释放
        
        if (isset($row[2]) && isset($row[3]) && isset($row[5]) && isset($row[6]) && isset($row[7]) 
             && $row[2] && $row[3]) 
        {
            $new_wallet = trim($row[3]);
            if ($new_wallet) 
            {
                $new_wallet = strtolower($new_wallet);
                $user = User::query()->where('wallet', $new_wallet)->first(['id','wallet']);
                
                $user_id = $user ? $user->id : 0;
                $old_wallet = strtolower($row[2]);
                $over_cash_usdt = @bcadd($row[5], '0', 6);
                $machine_total = @bcadd($row[6], '0', 6);
                $wait_cash_usdt = @bcadd($row[7], '0', 6);
                //待释放数量 = 中奖资产包总共多少U - 已释放未兑换成小黄鱼还有多少U- 已提现的
                $over_usdt = bcadd($over_cash_usdt, $wait_cash_usdt, 6);
                $machine_residue_total = bcsub($machine_total, $over_usdt, 6);
                
                return new OldUserDatum([
                    'user_id' => $user_id,
                    'old_wallet' => $old_wallet,
                    'new_wallet' => $new_wallet,
                    'over_cash_usdt' => $over_cash_usdt,
                    'machine_total' => $machine_total,
                    'wait_cash_usdt' => $wait_cash_usdt,
                    'machine_residue_total' => $machine_residue_total,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                
//                 $total = @bcadd($row[1], '0', 6);
//                 $machineDayRate = @bcadd(config('machine_day_rate'), '0', 6);
//                 if ($user && bccomp($total, '0', 6)>0 &&  bccomp($machineDayRate, '0', 6)>0) 
//                 {
//                     $residue_total = @bcadd($row[2], '0', 6);
//                     $residue_total = bccomp($total, $residue_total, 6)>=0 ? $residue_total : $total;
//                     if (bccomp($residue_total, '0', 6)>0) 
//                     {
//                         //累计中奖资产
//                         User::query()->where('id', $user->id)->increment('machine_win_total', $total);
//                         return new UserMachine([
//                             'ordernum' => get_ordernum(),
//                             'user_id' => $user->id,
//                             'total' => $total,
//                             'residue_total' => $residue_total,
//                             'rate' => $machineDayRate,
//                             'source' => 2,  //来源1互助拼团2后台导入
//                             'release_time' => $release_time,
//                             'created_at' => $date,
//                             'updated_at' => $date,
//                         ]);
//                     }
//                 }
                
            }
        } 
    }
    
    
    /**
     * 从第几行开始处理数据 就是不处理标题
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }
}
