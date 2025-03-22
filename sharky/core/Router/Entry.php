<?php

/**
 * @description 路由条目模块
 * @author Sharky
 * @date 2025-3-21
 * @version 1.1.0
 */

namespace Sharky\Core\Router;

class Entry
{
    readonly public mixed $method;
    readonly public string $path;
    readonly public array $params;
    readonly public mixed $callback;
    public $middleware;
 
    public function __construct($method, $path, $params, $callback, $middleware){
        $this->method = $method;
        $this->path = $path;
        $this->params = $params;
        $this->callback = $callback;
        $this->middleware = $middleware;
    }
    
    // 添加中间件到当前路由
    public function middleware($middleware)
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    //排除中间件
    public function withoutMiddleware($middleware){
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middleware = array_diff($this->middleware, $middleware);
        return $this;
    }
}