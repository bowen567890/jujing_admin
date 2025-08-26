<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class PoolConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'pool_config';
    
}
