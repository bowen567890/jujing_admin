<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;
use App\Models\Country;
use Illuminate\Validation\Rule;

class ResetPasswordFirstValidate extends BaseValidate
{


    public function rules(){
        return [
            'type' => 'required|in:1,2',
            'country' => ['required_if:type,1',Rule::in(Country::query()->where('status',1)->pluck('code')->toArray())],
            'phone' => 'required_if:type,1',
            'email' => 'required_if:type,2',
        ];
    }

}
