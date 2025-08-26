<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\BaseCode;
use Closure;
use Illuminate\Http\Request;
use App\Models\MyRedis;

class CheckUserLogin
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()){
            return responseJson([],406,'请登录');
        }
        
        if (env('CHECK_LAST_TOKEN'))
        {
            $user = auth()->user();
            $last_token = '';
            $MyRedis = new MyRedis();
            $lockKey = 'last_token:'.$user->id;
            if ($MyRedis->exists_key($lockKey)) {
                $last_token = $MyRedis->get_key($lockKey);
            } else {
                return responseJson([],406,'请登录');
            }
            
            //判断最后登入token
            $token = request()->header('Authorization', '');
            if (!$token || !$last_token || ($token!=$last_token)) {
                return responseJson([],406,'请登录');
            }
        }

        return $next($request);
    }
}
