<?php

/**
 * @description 启动文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

use Sharky\Core\Container;
use Sharky\Core\Router;

// 多站点自动加载函数
function autoloadClasses($className)
{
    $paths = explode("\\", $className);
    array_shift($paths);
    $className = array_pop($paths);
    $classPath = strtolower(implode(DIRECTORY_SEPARATOR, $paths));

    // 将命名空间转换为路径
    $classFile = implode(DIRECTORY_SEPARATOR, [SITE_ROOT,$classPath, $className]) . ".php";

    // 如果文件存在，加载该类文件
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}

// 注册自动加载函数
spl_autoload_register('autoloadClasses');

// 加载路由
Router::loadRoutes();

// 获取容器
$container = Container::getInstance();

// 通过容器创建框架App
$app = $container->make('app');

// 启动框架
$app->run();
