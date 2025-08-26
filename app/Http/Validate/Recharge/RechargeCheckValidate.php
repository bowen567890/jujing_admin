<?php

namespace App\Http\Validate\Recharge;

use App\Http\Validate\BaseValidate;

class RechargeCheckValidate extends BaseValidate
{


    public function rules(){

        return [
            'type' => 'required|in:1,2,3',
            'invest_id' => 'required|integer',
            'p_type' => 'requiredIf:type,1',
            'num' => '|regex:/^\d+(?:\.\d{1,9})?$/',
        ];
    }

}
