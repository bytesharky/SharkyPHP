<?php

/**
 * @description 中间件类
 * @author Sharky
 * @date 2025-3-21
 * @version 1.0.0
 */

namespace Sharky\Core;

// 中间件接口
interface MiddlewareInterface
{
    public function handle($request, callable $next);
}

// 中间件基类
abstract class Middleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        // 在这里执行中间件逻辑
        return $next($request);
    }
}