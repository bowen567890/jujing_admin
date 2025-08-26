<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class FeeOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'fee_order';
    
}
