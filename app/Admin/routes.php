<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->resource('users', 'UserController');
    $router->resource('tree', 'UserTreeController');
    $router->resource('recharge', 'RechargeController');
    $router->resource('banner', 'BannerController');
    $router->resource('bulletin', 'BulletinController');
    $router->resource('withdraw', 'WithdrawController');
    $router->resource('main_currency','MainCurrencyController');
    
    $router->resource('rank_config','RankConfigController');
    $router->resource('depth_config','DepthConfigController');
    $router->resource('user_usdt','UserUsdtController');
    $router->resource('rank_conf','RankConfigController');
    $router->resource('deep_config','DeepConfigController');
    
    $router->resource('ticket_currency','TicketCurrencyController');
    $router->resource('news','NewsController');
    
    $router->resource('user_usdt','UserUsdtController');
    
    $router->resource('node_config','NodeConfigController');
    $router->resource('node_order','NodeOrderController');
    $router->resource('ticket_config','TicketConfigController');
    $router->resource('user_ticket','UserTicketController');
    
    $router->resource('insurance_order','InsuranceOrderController');
    
    $router->resource('user_ranking_day','UserRankingDayController');
    $router->resource('pool_config','PoolConfigController');
    
    
    $router->any('auth/extensions',function (){
        die();
    });
});
