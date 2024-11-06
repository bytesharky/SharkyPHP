<?php

/**
 * @description 框架核心
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

class App
{
    protected $config;
    protected $router;

    // 初始化
    public function __construct(Router $router, Config $config)
    {
        try {
            // 初始化错误捕捉
            Exception::init();
            $this->config = $config;
            $this->router = $router;

            // 加载站点配置
            $this->config->loadConfigs();
            Exception::recover();


        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    // 启动应用
    public function run()
    {
        // 派发路由
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $this->router->dispatch($method, $uri);
    }
}
