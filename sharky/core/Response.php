<?php

/**
 * @description 响应类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;
use Exception;
use SimpleXMLElement;

class Response
{
    private string $contentType;
    private string $charset = 'utf-8';
    private array $headers = [];
    private int $guess;
    private int $status;
    private mixed $body = [];
    private string $restful = 'html';

    public function content_type($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    public function header($key, $value = '')
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (!is_string($k)) {
                    throw new \InvalidArgumentException('Header key 只能是字符串');
                }
                $this->header($k, $v);
            }
            return $this;
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $this->headers[$key] = $value;
        return $this;
    }

    public function body($body = [])
    {
        if (
            is_array($body) ||
            is_string($body) ||
            (is_bool($body) && $body)
        ) {
            $this->guess = 200;
            $this->body = $body;

        } elseif (is_int($body)) {
            $this->guess = $body;
            $this->body = null;

        } elseif (is_bool($body) && !$body) {
            $this->guess = 403;
            $this->body = null;

        } elseif (is_null($body)) {
            $this->guess = 200;
            $this->body = null;
        } else {
            throw new \InvalidArgumentException('Invalid body type');
        }

        return $this;
    }

    public function json($body = [], $status = null, $headers = [])
    {
        $this->restful = 'json';
        $this->body($body);
        $this->status($status ?? $this->guess);
        $this->header($headers);
        $this->content_type('application/json');
        return $this;
    }

    public function xml($body = [], $status = null, $headers = [])
    {
        $this->restful = 'xml';
        $this->body($body);
        $this->status($status ?? $this->guess);
        $this->header($headers);
        $this->content_type('application/xml');
        return $this;
    }
    
    public function html($body = '', $status = null, $headers = [])
    {
        $this->restful = 'html';
        $this->body($body);
        $this->status($status ?? $this->guess);
        $this->header($headers);
        $this->content_type('text/html');
        return $this;
    }

    public function status($status = 200)
    {
        $this->status = $status;
        return $this;
    }

    private function getContent($status)
    {
        $restful = $this->restful;

        $result = $this->fetchStatusJson($status);
        if (!empty($this->body)){
            $result['data'] = $this->body;
        }

        if (strtolower($restful) === "json") {
            return json_encode($result, JSON_UNESCAPED_UNICODE);

        } elseif (strtolower($restful) === "xml") {
            $config = Container::getInstance()->make('config');
            $xmlroot = $config->get('config.xmlroot', "root");
            $xmlData = new SimpleXMLElement("<?xml version=\"1.0\"?><$xmlroot></$xmlroot>");
            $this->arrayToXml( $result, $xmlData);
            return $xmlData->asXML();
        }

        if ($status === 200) {

            if (is_array($this->body) || is_object($this->body)) {
                $this->body = json_encode($this->body,JSON_UNESCAPED_UNICODE);
            } 
            return $this->body ?? '';

        } else {
            $errorTemplatePath = SHARKY_ROOT . '/errors/';
            $errorFile = $errorTemplatePath . "{$status}.php";
            if (file_exists($errorFile)) {
                ob_start();
                extract(['error' => $this->body, 'method' => $_SERVER['REQUEST_METHOD']]);
                include $errorFile;
                return ob_get_clean();
            } else {
                return ("{$status}: {$result['status']} - {$result['message']}");
            }
        }
    }

    private function arrayToXml($data, &$xmlData) {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = "item-{$key}";
            }
            if (is_array($value)) {
                $subnode = $xmlData->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xmlData->addChild($key, htmlspecialchars($value));
            }
        }
    }

    public function render($body, $status = null)
    {
        $this->body($body);
        $this->status($status ?? $this->guess);
        return $this;
    }

    public function redirect($url, $status = 302)
    {
        $this->body($url);
        $this->status($status);
        return $this;
    }

    public function __toString()
    {
        $callerInfo = debug_backtrace();
        $classname = $callerInfo[1]['class'];
        $function = $callerInfo[1]['function'];

        if ($classname !== 'Sharky\\Core\\App' || $function !== 'run') {
            return self::class;
        }

        if (!empty($this->contentType)) {
            $this->header("content-type", "{$this->contentType}; charset={$this->charset}");
        }

        if (
            !$this->in_header("content-type") &&
            !key_exists("content-type", array_change_key_case($this->headers, CASE_LOWER))
        ) {
            $config = Container::getInstance()->make('config');
            $this->restful = $config->get('config.restful', "");
            if (strtolower($this->restful) === "json") {
                $this->header("content-type", "application/json; charset={$this->charset}");

            } elseif (strtolower($this->restful) === "xml") {
                $this->header("content-type", "application/xml; charset={$this->charset}");

            } else {
                $this->header("content-type", "text/html; charset={$this->charset}");
            }
        }

        http_response_code($this->status);

        // 设置响应头
        foreach ($this->headers as $key => $value) {
            header(ucfirst($key) . ": {$value}");
        }

        // 处理重定向
        if (in_array($this->status, [301, 302, 303, 307, 308])) {
            if (is_array($this->body)) {
                $url = $body['Location'] ?? null;
            } else if (is_string($this->body)) {  
                $url = $this->body;
            }
            if (!empty($url)) {
                header("Location: {$url}", true, $this->status);
            }
            if (!$this->in_header('Location')) {
                throw new Exception('重定向地址不能为空');
            }
            return '';
        }



        return $this->getContent($this->status);
    }

    private function in_header($key)
    {
        $headers = headers_list();
        $isSet = array_filter($headers, function ($header) use ($key) {
            return stripos($header, $key) === 0;
        });
        return !empty($isSet);
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

        $status = 'Unknown Status';
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
