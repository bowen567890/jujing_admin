<?php


namespace App\Http\Validate;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseValidate extends FormRequest
{


    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code'=> 422,
            'msg'=> $validator->errors()->first(),
            'data'=> null
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE)));
    }

}
