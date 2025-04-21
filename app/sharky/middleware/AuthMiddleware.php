<?php

/**
 * @description 认证中间件
 * @author Sharky
 * @date 2025-3-21
 * @version 1.0.0
 */
namespace App\Middleware;

use Sharky\Core\Middleware;
use App\Controllers\Controller;

// 示例中间件 - 认证中间件
class AuthMiddleware extends Middleware
{
    public function handle($request, callable $next)
    {
        // 检查是否有认证信息
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($token)) {
            return (new Controller())->render("fail.html",[]);
        }
        
        // 验证令牌...
        // 示例: 如果令牌有效，则设置用户信息
        $request->user = ['id' => 1, 'name' => 'Test User'];
        
        // 继续执行下一个中间件或控制器
        return $next($request);
    }
}