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
    public $params = [];
    public $uri = '';
    public $method = '';
    public $attributes = [];
    
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }  

}
