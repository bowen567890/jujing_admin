<?php

namespace App\Http\Validate\Withdraw;

use App\Http\Validate\BaseValidate;

class RedemptionValidate extends BaseValidate
{

    public function rules(){

        return [
            'type' => 'required|in:pledge,lp',
            'invest_id' => 'required|integer',
        ];

    }

}
