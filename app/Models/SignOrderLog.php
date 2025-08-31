<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class SignOrderLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'sign_order_log';
    
}
