<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;
use App\Models\Country;
use Illuminate\Validation\Rule;

class LoginValidate extends BaseValidate
{

    public function rules(){

        return [
            'name' => 'required',
            'code' => 'max:10',
        ];
    }

}
