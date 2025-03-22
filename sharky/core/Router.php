<?php

/**
 * @description 路由管理模块
 * @author Sharky
 * @date 2025-3-21
 * @version 1.1.0
 */



namespace Sharky\Core;
use Sharky\Core\RouteNotFoundException;
use Sharky\Core\Container;
use Sharky\Core\Router\Entry;

class Router
{
    private static $routes = [];
    private static $groupOptions = [];
    private static $middleware = [];
    public const MATCH_START = 1;
    public const MATCH_END = 2;
    public const MATCH_FULL = 3;


    // 加载路由
    public static function loadRoutes()
    {
        $routesPath = implode(DIRECTORY_SEPARATOR, [SITE_ROOT, 'routes', ""]);
        foreach (glob($routesPath . '/*.php') as $file) {
            require_once $file; // 包含路由文件
        }
    }

    // 注册路由
    public static function reg($method, $path, $callback, $matchMode = -1)
    {
        $middleware = self::$middleware;
        if (isset(self::$groupOptions['prefix'])) {
            $path = rtrim(self::$groupOptions['prefix'], '/') . '/' . ltrim($path, '/');
        }
        if (isset(self::$groupOptions['controller']) && is_string($callback)) {
            $callback = [self::$groupOptions['controller'], $callback];
        }
        if (isset(self::$groupOptions['middleware'])){
            $middleware = array_merge(self::$groupOptions['middleware'], $middleware);
        }
        if ($matchMode === -1) {
            $matchMode = self::$groupOptions['matchMode'] ?? 3;
        }
        
        // 将路径格式化为正则表达式，并添加到路由表中
        $parse = self::formatPath($path, $matchMode);

        $route = new Entry(
            $method,
            $parse['path'],
            $parse['params'],
            $callback,
            middleware: $middleware
        );
        self::$middleware = [];
        self::$routes[] = $route;
        
        return $route; // 返回实例以支持链式操作
    }

    public static function preg_reg($method, $path, $callback, $params)
    {
        $middleware = self::$middleware;
        if (isset(self::$groupOptions['prefix'])) {
            $path = self::$groupOptions['prefix'].$path;
        }
        if (isset(self::$groupOptions['controller']) && is_string($callback)) {
            $callback = [self::$groupOptions['controller'], $callback];
        }
        if (isset(self::$groupOptions['middleware'])){
            $middleware = array_merge(self::$groupOptions['middleware'], $middleware);
        }

        $route = new Entry(
            $method,
            $path,
            $params,
            $callback,
            $middleware
        );
        self::$middleware = [];
        self::$routes[] = $route;
        
        return $route;
    }

    // 添加中间件到当前路由
    public static function middleware($middleware)
    {
        if (is_array($middleware)) {
            self::$middleware = array_merge(self::$middleware, $middleware);
        } else {
            self::$middleware[] = $middleware;
        }
        
        return new self(); // 返回实例以支持链式操作
    }

    // 注册路由分组
    public static function group(array $options, callable $callback)
    {
        // 保存当前分组选项
        $previousGroupOptions = self::$groupOptions;
        
        // 处理分组中间件
        if (!isset(self::$groupOptions['middleware'])) {
            self::$groupOptions['middleware'] = [];
        }
        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
        }else{
            $middleware = [];
        }

        $options['middleware'] = array_merge(self::$groupOptions['middleware'], self::$middleware, $middleware);
        
        self::$groupOptions = array_merge(self::$groupOptions, $options);
        
        // 执行分组回调，注册分组内的路由
        call_user_func($callback);
        
        // 恢复之前的分组选项
        self::$groupOptions = $previousGroupOptions;
        self::$middleware = [];
        return new self(); // 返回实例以支持链式操作
    }

    

    // 格式化路径
    private static function formatPath($path, $matchMode)
    {
        $regex = ($matchMode & 1) ? '/^' : "/";
        $params = [];
        $result = preg_replace_callback(
            '/\{([a-zA-Z_][\w]*)\}|[^\w]/',
            function ($matches) use (&$params) {
                if (isset($matches[1])) {
                    $params[] = $matches[1];
                    return '([^\/]+)';
                } else {
                    return preg_quote($matches[0], '/');
                }
            },
            $path
        );

        $regex .= $result;
        $regex .= ($matchMode & 2) ? '$/' : "/";
        return ["path" => $regex, "params" => $params];
    }

    // 派遣路由
    public function dispatch($method, $uri)
    {
        // 只保留?前的部分，即去除参数部分
        $uri = explode("?", $uri)[0];
        $routeExist = false;
        $method = strtoupper($method);

        foreach (self::$routes as $route) {
            if (preg_match($route->path, $uri, $params)) {
                // 找到了路由
                $routeExist = true;
                if (
                    ('ALL' === $route->method) ||
                    ($method === $route->method) ||
                    (is_array($route->method) && (
                        in_array('ALL', $route->method) ||
                        in_array($method, $route->method)))
                ) {
                    array_shift($params);
                    $length = count($route->params);
                    $params_ = array_pad(array_slice($params, 0, $length), $length, null);
                    $params_ = array_combine($route->params, $params_);
                    
                    // 创建请求上下文
                    $request = Container::getInstance()->make('request', [
                        'params' => $params_,
                        'uri' => $uri,
                        'method' => $method
                    ]);

                    
                    // 执行中间件链
                    $result = $this->runMiddlewareChain($route->middleware, $request, function($request) use ($route) {
                        // 执行控制器方法
                        return $this->callControllerMethod($route->callback, $request->params);
                    });
                    
                    return $this->renderRouter($result);
                }
            }
        }

        if ($routeExist) {
            // 返回405 Method Not Allowed
            return $this->renderRouter([
                'code' => 405,
                'status' => "fail",
                'message' => strtoupper($method) . ' Method Not Allowed'
            ]);
        } else {
            $container = Container::getInstance();
            $config = $container->make('config');
            $isDebug = $config->get('config.isdebug', false);
            if ($isDebug){
                throw new RouteNotFoundException("Route Not Found\n\n没有找到匹配的路由!\n\n$uri");
            }
            // 没有匹配的路由，返回404
            return $this->renderRouter([
                'code' => 404,
                'status' => "fail",
                'message' => 'Page Not Found'
            ]);
        }
    }

    // 执行中间件链
    private function runMiddlewareChain(array $middleware, $request, callable $finalCallback)
    {
        // 如果没有中间件，直接执行最终回调
        if (empty($middleware)) {
            return $finalCallback($request);
        }
        
        // 获取第一个中间件
        $firstMiddleware = array_shift($middleware);
        
        // 创建中间件实例
        $middlewareInstance = is_string($firstMiddleware) 
            ? Container::getInstance()->make($firstMiddleware) 
            : $firstMiddleware;
        
        // 执行中间件，传入下一个中间件作为回调
        return $middlewareInstance->handle($request, function($request) use ($middleware, $finalCallback) {
            return $this->runMiddlewareChain($middleware, $request, $finalCallback);
        });
    }

    // 调用控制器方法
    private function callControllerMethod($callback, $params)
    {
        if (is_array($callback) && is_string($callback[0])) {
            // 获取控制器和方法名称
            $controller = $callback[0];
            $method = $callback[1];
            // 通过容器注入依赖并实例化
            $container = Container::getInstance();
            $instance = $container->make($controller);
            // 调用实例化后的控制器方法
            return call_user_func_array([$instance, $method], $params);
        }

        // 如果是简单的回调函数
        return call_user_func_array($callback, $params);
    }

    private function renderRouter($res)
    {
        $renderRouter = ['Sharky\\Core\\Controller', 'renderRouter'];
        return $this->callControllerMethod($renderRouter, [$res]);
    }
    
    // 便捷方法 - GET
    public static function get($path, $callback, $matchMode = -1)
    {
        return self::reg('GET', $path, $callback, $matchMode);
    }
    
    // 便捷方法 - POST
    public static function post($path, $callback, $matchMode = -1)
    {
        return self::reg('POST', $path, $callback, $matchMode);
    }
    
    // 便捷方法 - PUT
    public static function put($path, $callback, $matchMode = -1)
    {
        return self::reg('PUT', $path, $callback, $matchMode);
    }
    
    // 便捷方法 - DELETE
    public static function delete($path, $callback, $matchMode = -1)
    {
        return self::reg('DELETE', $path, $callback, $matchMode);
    }
    
    // 便捷方法 - ALL HTTP方法
    public static function any($path, $callback, $matchMode = -1)
    {
        return self::reg('ALL', $path, $callback, $matchMode);
    }
}