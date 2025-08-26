<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class ExchangeValidate extends BaseValidate
{

    public function rules(){

        return [
            'from_coin' => 'required|in:usdt,bth',
            'num' => 'required|regex:/^\d+(?:\.\d{1,2})?$/',
            'to_coin' => 'required|in:usdt,bth'
        ];

    }

}
