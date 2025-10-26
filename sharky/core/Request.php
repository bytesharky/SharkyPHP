<?php

/**
 * @description 请求类
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

class Request
{
    public readonly array $params;
    public readonly string $uri;
    public readonly string $method;
    public readonly array $headers;
    public readonly string $body;
    public readonly string $clientIp;
    public readonly string $httpVersion;
    public readonly string $statrTime;
    private $attributes = [];

    public function __construct($params, $uri, $method)
    {
        $this->statrTime = time();
        $this->params = $params;
        $this->uri = $uri;
        $this->method = $method;
        $this->clientIp = $_SERVER['REMOTE_ADDR'];
        $this->httpVersion = $_SERVER['SERVER_PROTOCOL'];
        $this->proxys = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) : [];

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        $this->headers = array_reduce(array_keys($_SERVER), function ($carry, $key) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headerKey = ucwords(strtolower($headerKey), '-');
                $carry[$headerKey] = $_SERVER[$key];
            }
            return $carry;
        }, $headers??[]);


        $this->body = file_get_contents('php://input');
    }

    public function __get($name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($key, $value): void
    {
        $this->attributes[$key] = $value;
    }
}
