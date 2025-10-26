<?php

/**
 * @description 中间件模块
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

// 中间件接口
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next);
}

// 中间件基类
abstract class Middleware implements MiddlewareInterface
{

}