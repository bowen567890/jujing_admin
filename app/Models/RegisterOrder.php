<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class RegisterOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'register_order';
    
}
