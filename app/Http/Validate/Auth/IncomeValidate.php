<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class IncomeValidate extends BaseValidate
{


    public function rules()
    {

        return [
            'page' => 'nullable|integer',
            'size' => 'nullable|integer',
            'coin' => 'required|in:usdt,bth'
        ];

    }

}
