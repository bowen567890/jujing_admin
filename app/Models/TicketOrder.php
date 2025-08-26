<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class TicketOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'ticket_order';
    
}
