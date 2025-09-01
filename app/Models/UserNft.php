<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserNft extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'user_nft';
    
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
    public function nftconf(){
        return $this->hasOne(NftConfig::class, 'lv', 'lv');
    }
}
