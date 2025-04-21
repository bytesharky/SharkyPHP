<?php

/**
 * @description 日志中间件
 * @author Sharky
 * @date 2025-3-21
 * @version 1.0.0
 */
namespace App\Middleware;

use Sharky\Core\Middleware;

// 示例中间件 - 日志中间件
class LogMiddleware extends Middleware
{
    public function handle($request, callable $next)
    {
        // 记录请求开始时间
        $startTime = microtime(true);
        
        // 执行下一个中间件或控制器
        $response = $next($request);
        
        // 计算请求执行时间
        $executionTime = microtime(true) - $startTime;
        
        // 记录日志
        $logMessage = sprintf(
            "[%s] %s %s - %.4fs",
            date('Y-m-d H:i:s'),
            $request->method,
            $request->uri,
            $executionTime
        );
        
        // 这里可以将日志写入文件或数据库
        // file_put_contents(SITE_ROOT . '/logs/access.log', $logMessage . PHP_EOL, FILE_APPEND);
        
        return $response;
    }
}