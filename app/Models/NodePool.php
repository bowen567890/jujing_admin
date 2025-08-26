<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NodePool extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'node_pool';
    
}
