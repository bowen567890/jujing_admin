<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserTicket extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'user_ticket';
    
    public function ticket(){
        return $this->hasOne(TicketConfig::class,'id','ticket_id');
    }
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
