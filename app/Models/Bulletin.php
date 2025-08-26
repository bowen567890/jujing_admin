<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class Bulletin extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'bulletin';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'BulletinList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get(['id','title','title_en','title_fr','content','content_en','content_fr','created_at'])
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
        $key = 'BulletinList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
    
}
