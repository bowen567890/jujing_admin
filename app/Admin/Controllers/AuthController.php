<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Dcat\Admin\Layout\Content;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseAuthController
{

//    protected $view = 'admin.login';
//
//    // 重写你的登录页面逻辑
//    public function getLogin(Content $content)
//    {
//        if ($this->guard()->check()) {
//            return redirect($this->getRedirectPath());
//        }
////        $createSecret  = GoogleAuthenticator::CreateSecret();
////        $parameter = [["name"=>"usename","value"=>"MTR"]];
//        return $content->full()->body(view($this->view,[
////            'createSecret' => $createSecret,
////            'parameter' => $parameter,
//        ]));
//    }
//
//
//    public function postLogin(Request $request)
//    {
//        $credentials = $request->only([$this->username(), 'password']);
//        $remember = (bool) $request->input('remember', false);
//        $captcha = $request->input('captcha','');
//
//        /** @var \Illuminate\Validation\Validator $validator */
//        $validator = Validator::make($credentials, [
//            $this->username()   => 'required',
//            'password'          => 'required',
//        ]);
//
//        if ($validator->fails()) {
//            return $this->validationErrorsResponse($validator);
//        }
//
//        if (!GoogleAuthenticator::CheckCode(config('google.secret'),$captcha)){
//            return $this->validationErrorsResponse([
//                'captcha' => '谷歌验证码错误'
//            ]);
//        }
//
//        if ($this->guard()->attempt($credentials, $remember)) {
//            return $this->sendLoginResponse($request);
//        }
//
//        return $this->validationErrorsResponse([
//            $this->username() => $this->getFailedLoginMessage(),
//        ]);
//    }

}
