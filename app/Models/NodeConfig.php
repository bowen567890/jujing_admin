<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NodeConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'node_config';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'NodeConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->orderBy('lv', 'asc')
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
        $key = 'NodeConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
}
