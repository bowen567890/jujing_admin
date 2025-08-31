<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserNftLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'user_nft_log';
    
}
