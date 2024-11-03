<?php

/**
 * @description 常数文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

// 定义应用程序根目录
define('APP_ROOT', __DIR__ . '/../app');

// 定义框架目录常量
define('SHARKY_ROOT', __DIR__ . '/../sharky');

// 版本和版权信息
define('PROJECT', "SharkyPHP");
define('VERSION', "1.0.0");
define('COPYRIGHT', "Copyright © " .
((date('Y') > 2024) ? implode('-', range(2024, date('Y'))) : 2024)
. " Forgot Fish All rights reserved.");
