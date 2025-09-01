<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class SignOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'sign_order';
    
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
    
    /**
     * 设置缓存
     */
    public static function SetMonthListCache($Month='')
    {
        $time = time();
        if (!$Month) {
            $Month = date('Ym', $time);
        }
        
        $key = 'ThisMonthList:'.$Month;
        $MyRedis = new MyRedis();
        
        $list = [];
        $days = date('t');
        $btime = strtotime(date('Y-m-01 00:00:00', $time));
        for ($i=1; $i<=$days; $i++) 
        {
            $date = date('Y-m-d', $btime+86400*($i-1));
            $list[] = [
                'day' => $i,
                'today' => 0,
                'status' => 0,  //0未签到,1已签到,2未来
                'date' => $date
            ];
        }
        
        $MyRedis->setnx_lock($key, 2764800,serialize($list));
        return $list;
    }
    
    /**
     * 获取缓存
     */
    public static function GetMonthListCache($Month='')
    {
        $time = time();
        if (!$Month) {
            $Month = date('Ym', $time);
        }
        
        $key = 'ThisMonthList:'.$Month;
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetMonthListCache($Month);
        } else {
            return unserialize($list);
        }
    }
    
}
