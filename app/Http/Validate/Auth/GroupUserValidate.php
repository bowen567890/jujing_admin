<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class GroupUserValidate extends BaseValidate
{


    public function rules()
    {

        return [
            'page' => 'nullable|integer',
            'size' => 'nullable|integer',
        ];

    }

}
