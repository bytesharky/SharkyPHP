<?php

/**
 * @description Session管理器
 * @author Sharky
 * @date 2024-12
 * @version 1.0.0
 */

namespace Sharky\Libs;

class Session
{
    // 启动会话
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // 设置一个会话变量
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // 获取会话变量的值
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    // 检查会话变量是否存在
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    // 删除会话变量
    public static function delete($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    // 销毁会话
    public static function destroy()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_unset();
            session_destroy();
        }
    }
}
