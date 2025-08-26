<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiAuth
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
        if (env('SIGN_ENABLE')===true)
        {
            $now = time();
            $time = $request->input('time',0);
            $sign = $request->input('sign','');
            
            if (empty($time) || abs($now-$time)>60 || empty($sign)){
                return responseValidateError(__('error.非法请求'));
            }
            $params = $request->except(['sign','image']);
            ksort($params);
            $str = [];
            foreach($params as $k => $v) {
                if (is_array($v)){
                    continue;
                }
                // $v 为 array 递归拼接
                $str[] = $k .'='. rawurlencode($v);
            }
            if (empty(env('SIGN_KEY'))){
//                 return abort(403,'未设置接口加密KEY');
                return responseValidateError(__('error.非法请求'));
            }
            $signDes = implode('&',$str).'&'.env('SIGN_KEY');
            $handle_sign = strtoupper(md5($signDes));
            if ($handle_sign != $sign) {
//                 return abort(403,'非法请求');
                return responseValidateError(__('error.非法请求'));
            }
        }
        
        //判断请求来源
//         if (env('CHECK_REFERER'))
//         {
//             $site_url = config('site_url');
//             if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
//                 $refererUrl = $_SERVER['HTTP_REFERER'];
//                 if(strpos($refererUrl, $site_url)===false){
//                     return responseJson([],406,__('error.非法请求'));
// //                     return responseJsonAsServerError('非法请求');
//                 }
//             } else {
//                 return responseJson([],406,__('error.非法请求'));
// //                 return responseJsonAsServerError('非法请求');
//             }
//         }

        return $next($request);
    }
}
