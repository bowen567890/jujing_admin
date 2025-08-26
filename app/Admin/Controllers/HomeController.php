<?php

namespace App\Admin\Controllers;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;

use App\Admin\Metrics\TodayUsers;
use App\Admin\Metrics\TotalUsers;
use App\Admin\Metrics\TotalDogbee;
use App\Admin\Metrics\TotalWithdrawDogbee;
use App\Admin\Metrics\TotalLuckyPool;
use App\Admin\Metrics\TotalPower;
use App\Admin\Metrics\TotalYeji;
use App\Admin\Metrics\TotalUsdt;
use App\Admin\Metrics\TotalWithdrawUsdt;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->header('主页')
            ->description('')
            ->body(function (Row $row) {


                $row->column(12, function (Column $column) {
                    $column->row(function (Row $row){
                        $row->column(3,new TotalUsers());
                        $row->column(3,new TodayUsers());
//                         $row->column(3,new TotalDogbee());
//                         $row->column(3,new TotalWithdrawDogbee());
                    });
                });

                $row->column(12, function (Column $column) {
                    $column->row(function (Row $row){
//                         $row->column(3,new TotalLuckyPool());
//                         $row->column(3,new TotalPower());
//                         $row->column(3,new TotalUsdt());
//                         $row->column(3,new TotalWithdrawUsdt());
                    });
                });

                $row->column(12, function (Column $column) {
                    $column->row(function (Row $row){
//                         $row->column(3,new TotalYeji());
                    });
                });
                   

            });
    }
}
