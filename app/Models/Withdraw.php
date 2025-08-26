<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'withdraw';


    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
    public function feeorder(){
        return $this->hasOne(WithdrawFeeOrder::class,'id','fee_order_id');
    }
}
