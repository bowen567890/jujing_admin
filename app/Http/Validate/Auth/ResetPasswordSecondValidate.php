<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class ResetPasswordSecondValidate extends BaseValidate
{


    public function rules(){
        return [
            'token' => 'required',
            'password' => 'required|min:8|max:16',
            're_password' => 'required|same:password',
        ];
    }

}
