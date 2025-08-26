<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'recharge';
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
