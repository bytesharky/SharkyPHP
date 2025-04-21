<?php

/**
 * @description 常数文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

 // 版本和版权信息
define('PROJECT', "SharkyPHP");
define('VERSION', "1.2.1");
define('COPYRIGHT', getCopyright(2024,'Forgot Fish'));

// 定义框架目录常量
define('SHARKY_ROOT', realpath(__DIR__ . '/../sharky'));

// 定义应用程序根目录
define('APP_ROOT', realpath(__DIR__ . '/../app'));

// 站点目录
define('SITE_ROOT', getSitePath());

