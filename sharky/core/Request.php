<?php

/**
 * @description 请求类
 * @author Sharky
 * @date 2025-3-21
 * @version 1.0.0
 */

namespace Sharky\Core;

// 
class Request
{
    readonly public array $params;
    readonly public string $uri;
    readonly public string $method;
    private $attributes = [];
    
    public function __construct($params, $uri, $method){
        $this->params = $params;
        $this->uri = $uri;
        $this->method = $method;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }  

}
