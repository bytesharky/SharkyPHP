<?php

/**
 * @description 启动文件
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

use Sharky\Core\Container;
use Sharky\Core\Router;

// 注册自动加载函数
spl_autoload_register('autoloadClasses');

// 加载全局函数
if (file_exists(SITE_ROOT . '/common.php')) {
    require_once SITE_ROOT . '/common.php';
}

// 加载路由
Router::loadRoutes();

// 获取容器
$container = Container::getInstance();

// 通过容器创建框架App
$app = $container->make('app');

// 启动框架
$app->run();
