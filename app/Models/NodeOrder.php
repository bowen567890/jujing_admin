<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NodeOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'node_order';
    
    public function ticket(){
        return $this->hasOne(TicketConfig::class,'id','gift_ticket_id');
    }
    public function rank(){
        return $this->hasOne(RankConfig::class,'lv','gift_rank_id');
    }
    
    public function noderank(){
        return $this->hasOne(NodeConfig::class,'lv','lv');
    }
    
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
