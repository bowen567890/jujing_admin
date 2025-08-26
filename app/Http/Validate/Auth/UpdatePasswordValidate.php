<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class UpdatePasswordValidate extends BaseValidate
{

    public function rules(){

        return [
            'type' => 'required|in:1,2',
            'old_password' => 'required',
            'password' => 'required',
            're_password' => 'required|same:password',
            'sms_code' => 'required_if:type,2'
        ];

    }

}
