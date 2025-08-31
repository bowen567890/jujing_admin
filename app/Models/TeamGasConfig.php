<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class TeamGasConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'team_gas_config';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'TeamGasConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->orderBy('zhi_num', 'asc')
            ->orderBy('gas_rate', 'asc')
            ->get()
            ->toArray();
        if ($list) {
            $MyRedis->set_key($key, serialize($list));
            return $list;
        }
        if ($MyRedis->exists_key($key)) {
            $MyRedis->del_lock($key);
        }
        return [];
    }
    
    /**
     * 获取缓存
     */
    public static function GetListCache()
    {
        $key = 'TeamGasConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
    
    /**
     * 获取缓存
     */
    public static function GetGasRate($zhi_num=0)
    {
        $gas_rate = '0';
        $config = self::GetListCache();
        if ($config) 
        {
            foreach ($config as $val) {
                if ($zhi_num>=$val['zhi_num']) {
                    $gas_rate = $val['gas_rate'];
                }
            }
        }
        return $gas_rate;
    }
    
}
