<?php


namespace App\Http\Validate\Recharge;


use App\Http\Validate\BaseValidate;

class RechargeListValidate extends BaseValidate
{

    public function rules(){

        return [
            'page' => 'nullable|integer',
            'size' => 'nullable|integer',
//            'coin' => 'required|in:usdt,bth'
        ];

    }

}
