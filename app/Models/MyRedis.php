<?php
namespace App\Models;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
class MyRedis extends Model
{
    /**
     * 排队获得锁
     */
    public function add_lock($key = '', $timeout = 5)
    {
        $sleep_time = 10000;    //10毫秒
        $timeoutAt = time() + $timeout; //记录接口超时频率
        while (1) {
            if (Redis::set($key, 1, 'EX', $timeout+1, 'NX')) {
                //上锁成功
                return true;
                break;
            } else {
                if ($timeoutAt < microtime(true))
                {
                    return false;
                    break;
                } else {
                    usleep($sleep_time);//等待10ms
                }
            }
        }
        return false;
    }
    
    /**
     * 判断能否加锁
     */
    public function setnx_lock($key = '', $timeout = 5, $v = 1)
    {
        if (Redis::set($key, $v, 'EX', $timeout+1, 'NX')) {
            //上锁成功
            return true;
        }
        return false;
    }
    
    
    public function del_lock($lockKey)
    {
        Redis::del($lockKey);
    }
    
    public function set_key($key, $value)
    {
        Redis::set($key, $value);
    }
    
    public function get_key($key)
    {
        return Redis::get($key);
    }
    
    public function exists_key($key)
    {
        return Redis::exists($key);
    }
    
    public function incr($key)
    {
        return Redis::incr($key);
    }
    
    public function expire($key = '', $timeout = 5)
    {
        Redis::expire($key, $timeout);
    }
    
    public function setex($key, $timeout = 60, $value = '')
    {
        return Redis::setex($key, $timeout, $value);
    }
    
    
    /**
     *
     */
    public function getPtlock($key = '', $timeout = 5)
    {
        $sleep_time = 10000;    //10毫秒
        $timeoutAt = time() + $timeout; //记录接口超时频率
        while (1) {
            if (Redis::exists($key)) {
                return true;
                break;
            } else {
                if ($timeoutAt < microtime(true))
                {
                    return false;
                    break;
                } else {
                    usleep($sleep_time);//等待10ms
                }
            }
        }
        return false;
    }
}
