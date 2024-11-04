<?php

/**
 * @description 启动文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

use Sharky\Core\Container;
use Sharky\Core\Router;
use Sharky\Core\App;

// 加载路由
Router::loadRoutes();

// 创建容器
$container = Container::getInstance();

$container->bind('config');
$container->bind('router');
// $container->bind('app', function ($container) {
//     return new App($container->make('router'), $container->make('config'));
// });

// // 创建并启动框架
// $app = $container->make('app');
// $app->run();

// 这里改为直接创建App，通过容器
$app = new App($container->make('router'), $container->make('config'));
$app->run();
