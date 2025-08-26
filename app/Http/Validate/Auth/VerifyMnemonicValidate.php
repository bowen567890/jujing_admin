<?php


namespace App\Http\Validate\Auth;


use App\Http\Validate\BaseValidate;

class VerifyMnemonicValidate extends BaseValidate
{

    public function rules(){
        return [
            'mnemonic' => 'required',
        ];
    }

}
