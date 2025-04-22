<?php

/**
 * @description 路由配置文件
 * @author Sharky
 * @date 2025-4-23
 * @version 1.3.0
 */

use Sharky\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\DemoController;
use App\Middleware\AuthMiddleware;


/* 单条路由组注册实例 */
Router::reg('GET', '/', [HomeController::class, 'index']);
Router::reg('ALL', '/about', [HomeController::class, 'about']);
Router::reg(['GET','POST'], '/view', [HomeController::class, 'view']);
Router::reg(['GET','POST'], '/database', [HomeController::class, 'database']);
Router::reg('GET','/extension',[HomeController::class, 'extension']);

/* 路由中间件注册示例 */
Router::reg(['GET','POST'], '/auth', [HomeController::class, 'auth'])->middleware([
    AuthMiddleware::class
]);

Router::reg(['GET','POST'], '/child', [HomeController::class, 'child']);
/* 路由组注册实例 */
Router::middleware([
    AuthMiddleware::class
])->group(['prefix' => '/demo'], function () {
    Router::reg('GET', '/list', [DemoController::class, 'list'])->withoutMiddleware([
        AuthMiddleware::class
    ]);
    Router::reg('GET', '/{id}', [DemoController::class, 'show']);
    Router::reg('DELETE', '/{id}', [DemoController::class, 'delete']);
});
