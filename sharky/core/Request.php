<?php

/**
 * @description 请求类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;

// 
class Request
{
    readonly public array $params;
    readonly public string $uri;
    readonly public string $method;
    readonly public array $headers;

    private $attributes = [];
    
    public function __construct($params, $uri, $method){
        $this->params = $params;
        $this->uri = $uri;
        $this->method = $method;
        
        $this->headers = array_reduce(array_keys($_SERVER), function($carry, $key) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headerKey = ucwords(strtolower($headerKey), '-');
                $carry[$headerKey] = $_SERVER[$key];
            }
            return $carry;
        }, []);
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
