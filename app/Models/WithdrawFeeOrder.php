<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class WithdrawFeeOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'withdraw_fee_order';
    public function withdraw(){
        return $this->hasOne(Withdraw::class,'id','withdraw_id');
    }
}
