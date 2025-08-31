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
    $router->resource('user_juj','UserJujController');
    $router->resource('rank_conf','RankConfigController');
    $router->resource('deep_config','DeepConfigController');
    
    $router->resource('ticket_currency','TicketCurrencyController');
    $router->resource('news','NewsController');
    
    $router->resource('user_usdt','UserUsdtController');
    
    $router->resource('pool_config','PoolConfigController');
    
    $router->resource('nft_config','NftConfigController');
    $router->resource('nft_order','NftOrderController');
    $router->resource('user_nft_stat','UserNftStatController');
    $router->resource('user_nft_log','UserNftLogController');
    
    $router->resource('sign_order','SignOrderController');
    $router->resource('user_lock_order','UserLockOrderController');
    
    
    $router->resource('team_gas_config','TeamGasConfigController');
    
    
    
//     $router->any('auth/extensions',function (){
//         die();
//     });
});
