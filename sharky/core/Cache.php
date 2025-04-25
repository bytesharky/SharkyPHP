<?php

/**
 * @description 缓存控制类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;

class Cache
{
    private static $cachePath = SITE_ROOT . '/cache/'; // 缓存文件存储路径
    private static $defaultExpire = 3600; // 默认过期时间（秒）
    private static $isInit = false; // 是否已初始化
    private static $useFile = true; // 是否使用文件缓存（默认）
    private static $sessionId = null; // 会话 ID
    /**
     * 初始化缓存路径和默认设置
     */
    public static function init()
    {
        if (self::$isInit) {
            return;
        }
        // 如果缓存路径不存在，则创建
        if (!file_exists(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }

        $config = Container::getInstance()->make('config')->get('config.cache', []);
        if (is_array($config)) {
            if (isset($config['path'])) {
                self::$cachePath = rtrim($config['path'], '/') . '/';
            }
            if (isset($config['expire'])) {
                self::$defaultExpire = (int) $config['expire'];
            }
            if (isset($config['type'])) {
                if (!in_array($config['type'], ['redis', 'file'])) {
                    throw new \Exception("Invalid cache type: {$config['type']}");
                }
                self::$useFile = $config['type'] !== 'redis';
            }
        }

        self::$isInit = true;
    }

    /**
     * 设置文件缓存路径
     * @param string $path 缓存路径
     */
    public static function setCachePath($path)
    {
        if (!self::$isInit) {
            self::init();
        }
        self::$cachePath = rtrim($path, '/') . '/';

        // 如果路径不存在，则创建
        if (!file_exists(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }
    }

    public static function getCachePath()
    {
        if (!self::$isInit) {
            self::init();
        }
        return self::$cachePath;
    }

    public static function useFile()
    {
        if (!self::$isInit) {
            self::init();
        }
        self::$useFile = true;
    }

    public static function useRedis()
    {
        if (!self::$isInit) {
            self::init();
        }
        self::$useFile = false;
    }


    /**
     * 设置默认过期时间
     * @param int $seconds 过期时间（秒）
     */
    public static function setDefaultExpire($seconds)
    {
        self::$defaultExpire = (int) $seconds;
    }

    /**
     * 存储数据到缓存（默认使用 Redis）
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $expire 过期时间（秒），null 表示使用默认值
     * @return bool 是否存储成功
     */
    public static function set($key, $value, $expire = null, )
    {
        $sessionId = self::getSessionId();

        return self::setCache($key, $value, $sessionId,$expire);
    }

    public static function setShared($key, $value, $expire = null)
    {
        return self::setCache($key, $value, 'shared', $expire);
    }
    
    private static function setCache($key, $value, $sessionId, $expire = null)
    {
        if (!self::$isInit) {
            self::init();
        }

        $expire = $expire ?? self::$defaultExpire;

        if (self::$useFile) {
            return self::setFile($key, $value, $expire, $sessionId);
        }

        return self::setRedis($key, $value, $expire, $sessionId);
    }

    /**
     * 从缓存中获取数据（优先使用 Redis，若未找到则尝试文件缓存）
     * @param string $key 缓存键
     * @param mixed $default 默认值（未找到时返回）
     * @return mixed 缓存值或默认值
     */
    public static function get($key, $default = null)
    {
        $sessionId = self::getSessionId();

        return self::getCache($key, $sessionId, $default);
    }

    public static function getShared($key, $default = null)
    {
        return self::getCache($key, 'shared', $default);
    }

    private static function getCache($key, $sessionId, $default = null)
    {
        if (!self::$isInit) {
            self::init();
        }

        if (self::$useFile) {
            return self::getFile($key, $sessionId, $default);
        }

        $value = self::getRedis($key, $sessionId, $default);
        if ($value === null) {
            return self::getFile($key, $sessionId, $default);
        }

        return $value;
    }

    /**
     * 删除缓存
     * @param string $key 缓存键
     * @return bool 是否删除成功
     */
    public static function delete($key, )
    {
        $sessionId = self::getSessionId();
        return self::deleteCache($key, $sessionId);
    }

    public static function deleteShared($key)
    {
        return self::deleteCache($key, 'shared');  
    }

    private static function deleteCache($key, $sessionId)
    {
        if (!self::$isInit) {
            self::init();
        }

        if (self::$useFile) {
            return self::deleteFile($key, $sessionId);
        }

        $redisDeleted = self::deleteRedis($key, $sessionId);
        $fileDeleted = self::deleteFile($key, $sessionId);

        return $redisDeleted || $fileDeleted;
    }

    /**
     * 检查缓存键是否存在
     * @param string $key 缓存键
     * @return bool 是否存在
     */
    public static function exists($key)
    {
        $sessionId = self::getSessionId();
        return self::existsCache($key, $sessionId);
    }

    public static function existsShared($key)
    {
        return self::existsCache($key, 'shared');
    }

    
    private static function existsCache($key, $sessionId)
    {
        if (!self::$isInit) {
            self::init();
        }

        if (self::$useFile) {
            return self::existsFile($key, $sessionId);
        }

        return self::existsRedis($key, $sessionId) || self::existsFile($key, $sessionId);
    }

    // Redis 相关方法

    /**
     * 使用 Redis 存储数据
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间（秒）
     * @return bool 是否存储成功
     */
    private static function setRedis($key, $value, $expire, $sessionId)
    {
        $success = Redis::set("{$sessionId}:{$key}", serialize($value));
        if ($success && $expire > 0) {
            Redis::master()->expire("{$sessionId}:{$key}", $expire);
        }
        return $success;
    }

    /**
     * 从 Redis 获取数据
     * @param string $key 缓存键
     * @return mixed 缓存值或 null
     */
    private static function getRedis($key, $sessionId, $default = null)
    {
        $value = Redis::get("{$sessionId}:{$key}");
        return $value !== null ? unserialize($value) : $default;
    }

    /**
     * 删除 Redis 缓存
     * @param string $key 缓存键
     * @return bool 是否删除成功
     */
    private static function deleteRedis($key, $sessionId)
    {
        return Redis::delete("{$sessionId}:{$key}");
    }

    /**
     * 检查 Redis 缓存是否存在
     * @param string $key 缓存键
     * @return bool 是否存在
     */
    private static function existsRedis($key, $sessionId)
    {
        return Redis::exists("{$sessionId}:{$key}");
    }

    // 文件缓存相关方法

    /**
     * 使用文件存储数据
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间（秒）
     * @return bool 是否存储成功
     */
    private static function setFile($key, $value, $expire, $sessionId)
    {
        $filename = self::getFilename($key, $sessionId);
        $data = [
            'expire' => time() + $expire,
            'data' => $value
        ];

        $result = file_put_contents($filename, serialize($data));
        return $result !== false;
    }

    /**
     * 从文件缓存获取数据
     * @param string $key 缓存键
     * @param mixed $default 默认值（未找到时返回）
     * @return mixed 缓存值或默认值
     */
    private static function getFile($key, $sessionId, $default = null)
    {
        $filename = self::getFilename($key, $sessionId);

        if (!file_exists($filename)) {
            return $default;
        }

        $data = unserialize(file_get_contents($filename));
        if (!is_array($data) || !isset($data['expire'], $data['data'])) {
            unlink($filename);
            return $default;
        }

        if (time() > $data['expire']) {
            unlink($filename);
            return $default;
        }

        return $data['data'];
    }

    /**
     * 删除文件缓存
     * @param string $key 缓存键
     * @return bool 是否删除成功
     */
    private static function deleteFile($key, $sessionId)
    {
        $filename = self::getFilename($key, $sessionId);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    /**
     * 检查文件缓存是否存在
     * @param string $key 缓存键
     * @return bool 是否存在
     */
    private static function existsFile($key, $sessionId)
    {
        $filename = self::getFilename($key, $sessionId);
        if (!file_exists($filename)) {
            return false;
        }

        $data = unserialize(file_get_contents($filename));
        if (!is_array($data) || !isset($data['expire'])) {
            unlink($filename);
            return false;
        }

        if (time() > $data['expire']) {
            unlink($filename);
            return false;
        }

        return true;
    }

    /**
     * 根据缓存键生成文件名
     * @param string $key 缓存键
     * @return string 文件路径
     */
    private static function getFilename($key, $sessionId)
    {
        // 将缓存键转换为安全的文件名
        $safeKey = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $key);
        $prefix = $sessionId ? "{$sessionId}/" : "shared/";
        $userDir = self::$cachePath . $prefix;
        if (!file_exists($userDir)) {
            mkdir($userDir, 0755, true);
        }
        return $userDir . $safeKey . '.cache';
    }

    public static function getSessionId()
    {
        if (self::$sessionId) {
            return self::$sessionId;

        }else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['session_id'] ?? session_id();
    }

    public static function setSessionId($sessionId)
    {
        self::$sessionId = $sessionId;
    }
}