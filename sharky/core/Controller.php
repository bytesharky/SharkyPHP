<?php

/**
 * @description 控制器模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

class Controller
{
    protected $config;
    protected $project = "SharkyPHP";
    protected $version = "1.0.0";
    protected $error;

    // 初始化
    public function __construct()
    {
        $container = Container::getInstance();
        $config = $container->make('config');
        $this->config = $config;
    }

    // 渲染错误页面
    public function renderErrorPage($error)
    {
        $this->error = $error;
        $code = $error['code'] ?? 500;
        $message = $error['message'] ?? 'Internal Server Error';
        http_response_code($code);
        $errorTemplatePath = SHARKY_ROOT . '/errors/';
        $errorFile = $errorTemplatePath . "{$code}.php";
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo ("{$code} Error - {$message}");
        }
    }
}
