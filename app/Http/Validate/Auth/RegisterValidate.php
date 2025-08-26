<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;
use App\Models\Country;
use Illuminate\Validation\Rule;

class RegisterValidate extends BaseValidate
{


    public function rules(){

        return [
            'token' => 'required|string',
            'name' => 'required|max:100',
            'country' => ['required',Rule::in(Country::query()->where('status',1)->pluck('code')->toArray())],
            'phone' => [Rule::requiredIf(function (){
                return empty(request()->post('email'));
            }),'nullable','max:50'],
            'email' => [Rule::requiredIf(function (){
                return empty(request()->post('phone'));
            }),'nullable','email'],
            'password' => 'required|min:8|max:16',
            're_password' => 'required|same:password',
            'trade_password' => 'required|min:6',
            're_trade_password' => 'required|same:trade_password',
            'code' => 'required'
        ];

    }

}
