<?php

/**
 * @description 路由管理模块
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

use Sharky\Core\Container;
use Sharky\Core\Router\Entry;
use Sharky\Exceptions\RouteNotFoundException;

class Router
{
    private static $routes = [];
    private static $groupOptions = [];
    private static $middleware = [];
    private static $withoutMiddleware = [];
    private static $prefix = '';

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
    public static function reg($method, $path, $callback, $matchMode = -1): Entry
    {
        $middleware = self::$middleware;
        $subPath = preg_replace('/\/+/', '/', $path);
        $parse = self::formatPath($subPath);
        $params = $parse['params'];
        $path = $parse['path'];

        if (isset(self::$groupOptions['prefix'])) {
            $path = self::combineRegex(self::$groupOptions['prefix'], $path);
            $params = array_merge(self::$groupOptions['params'], $params);
        }
        if (isset(self::$groupOptions['controller']) && is_string($callback)) {
            $callback = [self::$groupOptions['controller'], $callback];
        }
        if (isset(self::$groupOptions['middleware'])) {
            $middleware = array_merge(self::$groupOptions['middleware'], $middleware);
        }
        if ($matchMode === -1) {
            $matchMode = self::$groupOptions['matchMode'] ?? 3;
        }

        // 设置匹配模式
        $regex = preg_replace_callback(
            '#^/((?:[^\\/]|\\\\.)*)/([a-zA-Z]*)$#',
            function ($matches) use ($matchMode) {
                $regex = (($matchMode & 1) ? '/^' : "/") . ltrim($matches[1]);
                $regex = rtrim($regex) . (($matchMode & 2) ? '$/' : "/");
                $regex .= $matches[2];
                return $regex;
            },
            $path
        );

        $route = new Entry(
            $method,
            $regex,
            $params,
            $callback,
            middleware: $middleware
        );
        self::$middleware = [];
        self::$routes[] = $route;

        return $route;
    }

    public static function preg_reg($method, $path, $callback, $params = [], $matchMode = -1): Entry
    {
        $middleware = self::$middleware;
        if (isset(self::$groupOptions['prefix'])) {
            $path = self::combineRegex(self::$groupOptions['prefix'], $path);
            $params = array_merge(self::$groupOptions['params'], $params);
        }
        if (isset(self::$groupOptions['controller']) && is_string($callback)) {
            $callback = [self::$groupOptions['controller'], $callback];
        }
        if (isset(self::$groupOptions['middleware'])) {
            $middleware = array_merge(self::$groupOptions['middleware'], $middleware);
        }

        if ($matchMode === -1) {
            $matchMode = self::$groupOptions['matchMode'] ?? 3;
        }

        // 设置匹配模式
        $regex = preg_replace_callback(
            '#^/((?:[^\\/]|\\\\.)*)/([a-zA-Z]*)$#',
            function ($matches) use ($matchMode) {
                $regex = (($matchMode & 1) ? '/^' : "/") . ltrim($matches[1]);
                $regex = rtrim($regex) . (($matchMode & 2) ? '$/' : "/");
                $regex .= $matches[2];
                return $regex;
            },
            $path
        );

        $route = new Entry(
            $method,
            $regex,
            $params,
            $callback,
            $middleware
        );
        self::$middleware = [];
        self::$routes[] = $route;

        return $route;
    }

    // 添加中间件到当前路由
    public static function middleware($middleware): Router
    {
        if (is_array($middleware)) {
            self::$middleware = array_merge(self::$middleware, $middleware);
        } else {
            self::$middleware[] = $middleware;
        }

        return new self();
    }

    // 从当前路由排除中间件
    public static function withoutMiddleware($middleware): Router
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }
        self::$middleware = array_diff(self::$middleware, $middleware);
        self::$withoutMiddleware = array_merge(self::$withoutMiddleware, $middleware);
        return new self();
    }

    // 添加路由前綴
    public static function prefix($prefix): Router
    {
        self::$prefix = $prefix;
        return new self();
    }

    // 注册路由分组
    public static function group(callable $callback): Router
    {
        // 保存当前分组选项
        $previousGroupOptions = self::$groupOptions;

        // 处理前缀
        $parse = self::formatPath(self::$prefix);

        if (isset(self::$groupOptions['prefix'])) {
            $options['prefix'] = self::combineRegex(self::$groupOptions['prefix'], $parse['path']);
            $options['params'] = self::$groupOptions['params'] ?? [];
            $options['params'] = array_merge((array) self::$groupOptions['params'], $parse['params']);
        } else {
            $options['prefix'] = $parse['path'];
            $options['params'] = $parse['params'];
        }

        // 处理分组中间件
        if (!isset(self::$groupOptions['middleware'])) {
            self::$groupOptions['middleware'] = [];
        }

        // 合并新添加的中间件
        $options['middleware'] = array_merge((array) self::$groupOptions['middleware'], self::$middleware);

        // 移除后排除的中间件
        $options['middleware'] = array_diff($options['middleware'], self::$withoutMiddleware);

        self::$prefix = '';
        self::$middleware = [];
        self::$withoutMiddleware = [];

        self::$groupOptions = array_merge(self::$groupOptions, $options);

        // 执行分组回调，注册分组内的路由
        call_user_func($callback);

        // 恢复之前的分组选项
        self::$groupOptions = $previousGroupOptions;

        return new self();
    }

    // 格式化路径
    private static function formatPath($path): array
    {
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

        return ["path" => "/{$result}/", "params" => $params];
    }

    // 简单合并路径
    private static function combineRegex(string ...$patterns): string
    {
        $escapedPatterns = [];
        $modifiers = '';
        foreach ($patterns as $pattern) {
            if (preg_match('#^/((?:[^\\/]|\\\\.)*)/([a-zA-Z]*)*$#', $pattern, $matches)) {
                $escapedPatterns[] = $matches[1];
                $modifiers .= $matches[2];
            } else {
                throw new \InvalidArgumentException("无效的正则表达式格式: {$pattern}。期望格式: /模式/修饰符");
            }
        }
        $modifiers = implode('', array_unique(str_split($modifiers)));
        return '/' . implode('', $escapedPatterns) . '/' . $modifiers;
    }

    // 派遣路由
    public function dispatch($method, $uri)
    {
        // 只保留?前的部分，即去除参数部分
        $uri = explode("?", $uri)[0];
        $routeExist = false;
        $method = strtoupper($method);

        // 处理预检请求
        if ($method === 'OPTIONS') {
            return $this->options($uri);
        }

        foreach (self::$routes as $route) {

            if (preg_match($route->path, $uri, $matches)) {
                $routeExist = true;
                if (
                    ('ALL' === $route->method) ||
                    ($method === $route->method) ||
                    (is_array($route->method) && (
                        in_array('ALL', $route->method) ||
                        in_array($method, $route->method)))
                ) {
                    array_shift($matches);
                    $length = count($route->params);
                    $params = array_pad(array_slice($matches, 0, $length), $length, null);
                    $params = array_combine($route->params, $params);

                    // 创建请求上下文
                    $request = Container::getInstance()->make(Request::class, [
                        'params' => $params,
                        'uri' => $uri,
                        'method' => $method
                    ]);

                    // 执行中间件链
                    $result = $this->runMiddlewareChain(
                        $route->middleware,
                        $request,
                        fn($request) => $this->callControllerMethod($route->callback, $request->params)
                    );

                    // 如果结果是响应对象，直接返回
                    if ($result instanceof Response) {
                        $this->addCorsheaders($request, $result);
                        return $result;
                    }

                    // 否则创建响应上下文
                    $response = Container::getInstance()->make(Response::class);
                    $response->render($result);
                    $this->addCorsheaders($request, $response);
                    return $response;
                }
            }
        }

        $response = Container::getInstance()->make(Response::class);
        $request = Container::getInstance()->make(Request::class, [
            'params' => [],
            'uri' => $uri,
            'method' => $method,
        ]);

        if ($routeExist) {

            // 返回405 Method Not Allowed
            $response->render([
                'code' => 405,
                "status" => "error",
                'message' => 'Method Not Allowed. The requested method is not supported for this resource.',
                'method' => $method,
                'uri' => $uri,
            ], 405);
            $this->addCorsheaders($request, $response);
            return $response;
        } else {
            $container = Container::getInstance();
            $config = $container->make('config');
            $isDebug = $config->get('config.isdebug', false);
            if ($isDebug) {
                throw new RouteNotFoundException("Route Not Found\n\n没有找到匹配的路由!\n\n$uri");
            }
            // 没有匹配的路由，返回404
            $response->render([
                'code' => 404,
                "status" => "error",
                'message' => 'Not Found. The requested resource could not be found.',
                'method' => $method,
                'uri' => $uri,
            ], 404);

            $this->addCorsheaders($request, $response);
            return $response;
        }
    }

    // 预检路由
    private function options($uri): mixed
    {
        $support = [];
        foreach (self::$routes as $route) {
            if (preg_match($route->path, $uri, $matches)) {
                $methods = is_array($route->method) ? $route->method : [$route->method];
                $support = array_merge($support, $methods);
            }
        }
        // 创建请求上下文
        $request = Container::getInstance()->make(Request::class, [
            'params' => [],
            'uri' => $uri,
            'method' => 'OPTIONS'
        ]);

        $config = Container::getInstance()->make(Config::class);
        $headers = $config->get('config.cors.headers', []);
        $headers = is_array($headers) ? implode(", ", $headers) : $headers;

        $response = response()->header([
            'Access-Control-Allow-Methods' => implode(", ", $support) . ', OPTIONS',
            'Access-Control-Allow-Headers' => $headers
        ])->status(204);

        if ($this->addCorsheaders($request, $response)) {
            return $response;
        }
        return response()->status(204);
    }

    // 添加 CORS 相应头
    private function addCorsheaders(Request $request, Response &$response): bool
    {
        // 验证是否在允许跨域白名单
        $config = Container::getInstance()->make(Config::class);
        $origin = $request->headers['Origin'] ?? '';
        $allows = $config->get('config.cors.whitelist', []);
        $allows = is_array($allows) ? $allows : [$allows];

        // 添加支持跨域响应头
        foreach ($allows as $allowed) {
            if (fnmatch($allowed, $origin, FNM_CASEFOLD)) {
                $response->header([
                    'Access-Control-Allow-Origin' => $origin,
                    'Access-Control-Allow-Credentials' => 'true',
                ]);
                return true;
            }
        }
        return false;
    }


    // 执行中间件链
    private function runMiddlewareChain(array $middleware, $request, callable $finalCallback): mixed
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
        return $middlewareInstance->handle($request, function ($request) use ($middleware, $finalCallback): mixed {
            return $this->runMiddlewareChain($middleware, $request, finalCallback: $finalCallback);
        });
    }

    // 调用控制器方法
    private function callControllerMethod($callback, $params): mixed
    {
        if (is_array($callback) && is_string($callback[0])) {
            // 获取控制器和方法名称
            $controller = $callback[0];
            $method = $callback[1];
            // 通过容器注入依赖并实例化
            $container = Container::getInstance();
            $instance = $container->make($controller);
            // 反射方法所需的参数
            $reflection = new \ReflectionMethod($instance, $method);
            $methodParams = $reflection->getParameters();
            $callParams = [];
            foreach ($methodParams as $param) {
                $name = $param->getName();
                if (array_key_exists($name, $params)) {
                    $callParams[] = $params[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $callParams[] = $param->getDefaultValue();
                } else {
                    // 参数缺失且无默认值
                    $callParams[] = null;
                }
            }
            // 调用实例化后的控制器方法
            return call_user_func_array([$instance, $method], $callParams);
        }

        // 如果是简单的回调函数
        return call_user_func_array($callback, $params);
    }

    // 便捷方法 - GET
    public static function get($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('GET', $path, $callback, $matchMode);
    }

    // 便捷方法 - POST
    public static function post($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('POST', $path, $callback, $matchMode);
    }

    // 便捷方法 - PUT
    public static function put($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('PUT', $path, $callback, $matchMode);
    }

    // 便捷方法 - DELETE
    public static function delete($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('DELETE', $path, $callback, $matchMode);
    }

    // 便捷方法 - PATCH
    public static function patch($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('PATCH', $path, $callback, $matchMode);
    }

    // 便捷方法 - ALL HTTP方法
    public static function any($path, $callback, $matchMode = -1): Entry
    {
        return self::reg('ALL', $path, $callback, $matchMode);
    }
}