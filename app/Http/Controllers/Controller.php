<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $whiteIdList = [2,3,4,5,6,7];
    
    public function __construct()
    {
        $lang = request()->header('lang','zh_CN');
        if (!$lang) {
            $lang = 'zh_CN';
        }
        App::setLocale($lang);
    }

}
