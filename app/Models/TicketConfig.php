<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class TicketConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'ticket_config';
    
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'TicketConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->orderBy('ticket_price', 'asc')
            ->get(['id','ticket_price','insurance','status','ticket_sale'])
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
        $key = 'TicketConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
}
