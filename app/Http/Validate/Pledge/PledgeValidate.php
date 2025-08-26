<?php


namespace App\Http\Validate\Pledge;


use App\Http\Validate\BaseValidate;

class PledgeValidate extends BaseValidate
{

    public function rules(){

        return [
            'invest_id' => 'required|integer',
        ];

    }

}
