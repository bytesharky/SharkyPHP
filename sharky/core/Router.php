<?php

/**
 * @description 路由管理模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

class Router
{
    private static $routes = [];
    private static $groupOptions = [];

    // 加载路由
    public static function loadRoutes()
    {
        $routesPath = implode(DIRECTORY_SEPARATOR, [SITE_ROOT, 'routes', ""]);
        foreach (glob($routesPath . '/*.php') as $file) {
            require_once $file; // 包含路由文件
        }
    }

    // 注册路由
    public static function reg($method, $path, $callback)
    {
        // 应用分组的 prefix 和 controller 配置
        if (isset(self::$groupOptions['prefix'])) {
            $path = rtrim(self::$groupOptions['prefix'], '/') . '/' . ltrim($path, '/');
        }
        if (isset(self::$groupOptions['controller']) && is_string($callback)) {
            $callback = [self::$groupOptions['controller'], $callback];
        }

        // 将路径格式化为正则表达式，并添加到路由表中
        self::$routes[] = [
            'method' => $method,
            'path' => self::formatPath($path),
            'callback' => $callback
        ];
    }

    // 注册路由分组
    public static function group(array $options, callable $callback)
    {
        // 保存当前分组选项
        $previousGroupOptions = self::$groupOptions;
        self::$groupOptions = array_merge(self::$groupOptions, $options);
        // 执行分组回调，注册分组内的路由
        call_user_func($callback);
        // 恢复之前的分组选项
        self::$groupOptions = $previousGroupOptions;
    }

    // 格式化路径
    private static function formatPath($path)
    {
        $parts = explode('/', $path);
        $regex = '/^';
        $isFirst = true;
        foreach ($parts as $part) {
            if (strpos($part, '{') !== false && strpos($part, '}') !== false) {
                $variable = substr($part, 1, -1);
                $regex .= ($isFirst ? '' : '\/') . '([^\/]+)';
                $isFirst = false;
            } else {
                $regex .= ($isFirst ? '' : '\/') . preg_quote($part, '/');
                $isFirst = false;
            }
        }
        $regex .= '$/';
        return $regex;
    }

    // 派遣路由
    public function dispatch($method, $uri)
    {
        // 只保留?前的部分，即去除参数部分
        $uri = explode("?", $uri)[0];
        $routeExist = false;
        $method = strtoupper($method);
        // 去除结尾的斜杠以确保准确匹配
        $uri = ($uri != '/') ? rtrim($uri, '/') : $uri;
        foreach (self::$routes as $route) {
            if (preg_match($route['path'], $uri, $params)) {
                // 找到了路由
                $routeExist = true;
                if (
                    ('ALL' === $route['method']) ||
                    ($method === $route['method']) ||
                    (is_array($route['method']) && (
                        in_array('ALL', $route['method']) ||
                        in_array($method, $route['method'])))
                ) {
                    array_shift($params);
                    // 取出匹配到的参数
                    return $this->callControllerMethod($route['callback'], $params);
                }
            }
        }

        if ($routeExist) {
            // 返回405 Method Not Allowed
            $errCallback = ['Sharky\\Core\\Controller', 'renderErrorPage'];
            return $this->callControllerMethod($errCallback, [
                [
                    'code' => 405,
                    'method' => $method,
                    'message' => 'Method Not Allowed'
                ]
            ]);
        } else {
            // 没有匹配的路由，返回404
            $errCallback = ['Sharky\\Core\\Controller', 'renderErrorPage'];
            return $this->callControllerMethod($errCallback, [
                [
                    'code' => 404,
                    'message' => 'Page Not Found'
                ]
            ]);
        }
    }

    // 调用控制器方法
    private function callControllerMethod($callback, $params)
    {

        if (is_array($callback) && is_string($callback[0])) {
            // 实例化控制器
            $controller = new $callback[0]();
            $method = $callback[1];
            // 调用实例化后的控制器方法
            return call_user_func_array([$controller, $method], $params);
        }

        // 如果是简单的回调函数
        return call_user_func_array($callback, $params);
    }
}
