<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NftOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'nft_order';
    
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
    public function nftconf(){
        return $this->hasOne(NftConfig::class, 'lv', 'lv');
    }
}
