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
    protected $request;

    // 初始化
    public function __construct()
    {
        $container = Container::getInstance();
        $config = $container->make('config');
        $request = $container->make('request');
        $this->config = $config;
        $this->request = $request;
    }

    // 渲染路由页面
    public function renderRouter($response)
    {
        $restful = $this->config->get('config.restful', "");
        if (is_array($response) || is_string($response)) {
            $json = $response;
        } else if (is_int($response)) {
            $json = $this->fetchStatusJson($response);
        } else if (is_null($response)) {
            $json = "";
        } else if (is_bool($response) && $response) {
            $json = $this->fetchStatusJson(200);
        } else {
            $json = [
                'code' => 500,
                'status' => 'Server Error',
                'message' => 'Internal Server Error',
            ];
        }

        $code = intval($json['code']??200);
        http_response_code($code);

        if (is_array($json)) {
            $errMsg = $this->fetchStatusJson($code);
            $json['message'] = $json['message'] ?? $errMsg["message"];
            $json['status'] = $json['status'] ?? $errMsg["status"];
        }
        
        if ($code == 200) {
            if (is_array($json)) {
                return (json_encode($json));
            } else {
                return ($json);
            }
        } else {
            if (in_array($code, [301, 302, 303, 307, 308])) {
                if (array_key_exists('Location', $json)) {
                    header('Location: ' . $json['Location']);
                }
            }
            if (strtolower($restful) === "json") {
                return (json_encode($json));
            } else {
                $errorTemplatePath = SHARKY_ROOT . '/errors/';
                $errorFile = $errorTemplatePath . "{$code}.php";
                if (file_exists($errorFile)) {
                    ob_start();
                    extract(['error' => $json, 'method' => $_SERVER['REQUEST_METHOD']]);
                    include $errorFile;
                    return ob_get_clean();
                } else {
                    $status = $json['status'] ?? 'Unknown Status';
                    $message = $json['message'] ?? 'Unknown Error';
                    return ("{$status}: {$code} - {$message}");
                }
            }
        }
    }

    private function fetchStatusJson($statusCode)
    {
        $statusMessages = [
            100 => "Continue",
            101 => "Switching Protocols",
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",
            308 => "Permanent Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Request Failed",
            408 => "Request Time-out",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-Uri Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            500 => "Internal Server Error",
            501 => "Internal Server Error",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Time-out",
            505 => "Version Not Supported"
        ];

        $status = '';
        if ($statusCode >= 100 && $statusCode < 200) {
            $status = 'Informational';
        } elseif ($statusCode >= 200 && $statusCode < 300) {
            $status = 'Successful';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            $status = 'Redirection';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $status = 'Client Error';
        } elseif ($statusCode >= 500 && $statusCode < 600) {
            $status = 'Server Error';
        }

        $data = [
            'code' => $statusCode,
            'status' => $status,
            'message' => isset($statusMessages[$statusCode]) ? $statusMessages[$statusCode] : 'Unknown Error'
        ];
        return $data;
    }
}
