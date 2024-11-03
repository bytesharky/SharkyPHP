<?php

/**
 * @description Cookie管理器
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Libs;

class Cookie
{
    // 设置一个 cookie
    public static function set(
        $key,
        $value,
        $expiry = 3600,
        $path = "/",
        $domain = "",
        $secure = false,
        $httponly = true
    ) {
        setcookie($key, $value, time() + $expiry, $path, $domain, $secure, $httponly);
    }

    // 获取 cookie 的值
    public static function get($key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    // 检查 cookie 是否存在
    public static function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    // 删除 cookie
    public static function delete($key, $path = "/", $domain = "")
    {
        if (isset($_COOKIE[$key])) {
            setcookie($key, '', time() - 3600, $path, $domain);
            unset($_COOKIE[$key]);
        }
    }
}
