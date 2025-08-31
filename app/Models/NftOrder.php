<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NftOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'nft_order';
    
}
