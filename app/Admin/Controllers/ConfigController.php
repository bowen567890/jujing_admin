<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Setting;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;

class ConfigController extends Controller
{

    public function setting(Content $content){
        return $content
            ->title('其他配置')
            ->body(new Card(new Setting()));
    }
}
