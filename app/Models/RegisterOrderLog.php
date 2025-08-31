<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class RegisterOrderLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'register_order_log';
    
}
