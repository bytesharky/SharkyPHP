<?php

/**
 * @description 控制器模块
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

class Controller
{
    protected Config $config;
    protected Request $request;
    protected Response $response;

    // 初始化
    public function __construct()
    {
        $container = Container::getInstance();
        $config = $container->make('config');
        $request = $container->make('request');
        $response = $container->make('response');
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;
    }

    protected function response($body, $status = 200){
        return $this->response->render($body, $status);
    }

}
