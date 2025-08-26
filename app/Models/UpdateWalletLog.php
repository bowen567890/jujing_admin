<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UpdateWalletLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'update_wallet_log';
    
}
