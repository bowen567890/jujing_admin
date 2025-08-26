<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class BitQuery extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'bit_query';
    
}
