<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserUsdt extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'user_usdt';
    
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
