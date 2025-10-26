<?php

/**
 * @description 框架核心
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;
use Sharky\Exceptions\SharkyException;

class App
{
    protected $config;
    protected $router;

    // 初始化
    public function __construct(Router $router, Config $config)
    {
        try {
            // 初始化错误捕捉
            SharkyException::init();
            $this->config = $config;
            $this->router = $router;

            // 加载站点配置
            $this->config->loadConfigs();
            SharkyException::recover();


        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    // 启动应用
    public function run(): void
    {
        // 开启缓冲区
        ob_start();

        // 设置时区
        $timezoneId = config('config.timezone', 'Asia/Shanghai');
        date_default_timezone_set($timezoneId);

        // 派遣路由
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        $response = $this->router->dispatch($method, $uri);

        // 清空缓冲区
        ob_clean();

        echo $response;

        // 输出并关闭缓冲区
        ob_end_flush();
    }
}
