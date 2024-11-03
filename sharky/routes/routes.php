<?php

/**
 * @description 路由配置文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

use Sharky\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\DemoController;
use App\Controllers\NewsController;

/* 单条路由组注册实例 */
Router::reg('GET', '/', [HomeController::class, 'index']);
Router::reg('ALL', '/about', [HomeController::class, 'about']);
Router::reg(['GET','POST'], '/view', [HomeController::class, 'view']);
Router::reg(['ALL','POST'], '/database', [HomeController::class, 'database']);
/* 路由组注册实例 */
Router::group(['prefix' => '/demo'], function () {

    Router::reg('GET', '/list', [DemoController::class, 'list']);
    Router::reg('GET', '/{id}', [DemoController::class, 'show']);
    Router::reg('DELETE', '/{id}', [DemoController::class, 'delete']);
});

/* 路由组注册实例
Router::group(['controller' => NewsController::class, 'prefix' => '/news'], function(){
    Router::reg('GET', '/{id}', 'show');
    Router::reg('DELETE', '/{id}', 'delete');
});
*/
