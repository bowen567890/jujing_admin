<?php


namespace App\Http\Validate\Pledge;


use App\Http\Validate\BaseValidate;

class UnlockValidate extends BaseValidate
{

    public function rules(){

        return [
            'num' => 'required|regex:/^\d+(?:\.\d{1,2})?$/'
        ];

    }

}
