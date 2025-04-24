<?php

/**
 * @description 控制器模块
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;

class Controller
{
    protected $config;
    protected $error;
    protected $request;
    protected $response;

    // 初始化
    public function __construct()
    {
        $container = Container::getInstance();
        $config = $container->make('config');
        $request = $container->make('request');
        $response = $container->make('response');
        $this->config = $config;
        $this->request = $request;
    }

}
