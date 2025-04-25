<?php

/**
 * @description Redis数据库操作类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;

use Sharky\Core\Redis\Pool;
use Sharky\Core\Redis\Connect;
use Exception;

class Redis
{
    private static $config = null;
    private static $connects = [];

    /**
     * 加载Redis配置
     * @return array
     */
    private static function loadConfig(): array
    {
        if (self::$config) {
            return self::$config;
        }

        $container = Container::getInstance();
        if (!$container->make('config')) {
            throw new Exception("Config service not found in container");
        }

        self::$config = $container->make('config')->get('redis', []);

        if (empty(self::$config)) {
            throw new Exception("Redis configuration not found");
        }

        return self::$config;
    }

    /**
     * 获取Redis连接池实例
     * @param string $name 配置名称
     * @return Pool
     * @throws Exception
     */
    public static function connects(string $name = 'default'): Pool
    {
        if (isset(self::$connects[$name])) {
            return self::$connects[$name];
        }

        $config = self::loadConfig();

        if (!isset($config[$name])) {
            throw new Exception("Redis configuration '{$name}' not found");
        }

        // 创建连接池
        $pool = new Pool($config[$name]);
        
        // 设置前缀
        if (isset($config[$name]['prefix'])) {
            $pool->prefix($config[$name]['prefix']);
        }

        self::$connects[$name] = $pool;

        return $pool;
    }

    /**
     * 获取从库连接
     * @return Connect
     * @throws Exception
     */
    public static function slave(): Connect
    {
        return self::connects()->slave();
    }

    /**
     * 获取主库连接
     * @return Connect
     * @throws Exception
     */
    public static function master(): Connect
    {
        $config = self::loadConfig();
        $pool = self::connects();

        // 粘性连接处理
        if (isset($config['sticky']) && $config['sticky']) {
            $master = $pool->master();
            $pool->setSticky($master);
            return $master;
        }

        return $pool->master();
    }

    // ==================== 基本操作 ====================

    /**
     * 设置键前缀
     * @param string $prefix
     * @return Pool
     */
    public static function prefix(string $prefix): Pool
    {
        return self::connects()->prefix($prefix);
    }

    /**
     * 选择数据库
     * @param int $db
     * @return Pool
     */
    public static function select(int $db): Pool
    {
        return self::connects()->select($db);
    }

    // ==================== 字符串操作 ====================

    /**
     * 设置键值
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl 过期时间(秒)
     * @return bool
     */
    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        return self::master()->set($key, $value, $ttl);
    }

    /**
     * 获取键值
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return self::slave()->get($key);
    }

    /**
     * 删除键
     * @param string|array $key
     * @return int
     */
    public static function delete($key): int
    {
        return self::master()->delete($key);
    }

    /**
     * 检查键是否存在
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        return self::slave()->exists($key);
    }

    /**
     * 设置键的过期时间
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public static function expire(string $key, int $ttl): bool
    {
        return self::master()->expire($key, $ttl);
    }

    /**
     * 设置键在指定时间戳过期
     * @param string $key
     * @param int $timestamp
     * @return bool
     */
    public static function expireAt(string $key, int $timestamp): bool
    {
        return self::master()->expireAt($key, $timestamp);
    }

    /**
     * 获取键的剩余生存时间
     * @param string $key
     * @return int
     */
    public static function ttl(string $key): int
    {
        return self::slave()->ttl($key);
    }

    /**
     * 键不存在时设置值
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function setnx(string $key, $value): bool
    {
        return self::master()->setnx($key, $value);
    }

    /**
     * 批量设置键值
     * @param array $items
     * @return bool
     */
    public static function mset(array $items): bool
    {
        return self::master()->mset($items);
    }

    /**
     * 批量获取键值
     * @param array $keys
     * @return array
     */
    public static function mget(array $keys): array
    {
        return self::slave()->mget($keys);
    }

    // ==================== 哈希操作 ====================

    /**
     * 设置哈希字段值
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @return int
     */
    public static function hSet(string $key, string $field, $value): int
    {
        return self::master()->hSet($key, $field, $value);
    }

    /**
     * 获取哈希字段值
     * @param string $key
     * @param string $field
     * @return mixed
     */
    public static function hGet(string $key, string $field)
    {
        return self::slave()->hGet($key, $field);
    }

    /**
     * 批量设置哈希字段
     * @param string $key
     * @param array $fields
     * @return bool
     */
    public static function hMSet(string $key, array $fields): bool
    {
        return self::master()->hMSet($key, $fields);
    }

    /**
     * 批量获取哈希字段
     * @param string $key
     * @param array $fields
     * @return array
     */
    public static function hMGet(string $key, array $fields): array
    {
        return self::slave()->hMGet($key, $fields);
    }

    /**
     * 获取所有哈希字段和值
     * @param string $key
     * @return array
     */
    public static function hGetAll(string $key): array
    {
        return self::slave()->hGetAll($key);
    }

    // ==================== 列表操作 ====================

    /**
     * 左推入列表
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public static function lPush(string $key, $value): int
    {
        return self::master()->lPush($key, $value);
    }

    /**
     * 右推入列表
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public static function rPush(string $key, $value): int
    {
        return self::master()->rPush($key, $value);
    }

    /**
     * 左弹出列表
     * @param string $key
     * @return mixed
     */
    public static function lPop(string $key)
    {
        return self::master()->lPop($key);
    }

    /**
     * 右弹出列表
     * @param string $key
     * @return mixed
     */
    public static function rPop(string $key)
    {
        return self::master()->rPop($key);
    }

    /**
     * 获取列表长度
     * @param string $key
     * @return int
     */
    public static function lLen(string $key): int
    {
        return self::slave()->lLen($key);
    }

    // ==================== 集合操作 ====================

    /**
     * 添加集合元素
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public static function sAdd(string $key, $value): int
    {
        return self::master()->sAdd($key, $value);
    }

    /**
     * 获取所有集合成员
     * @param string $key
     * @return array
     */
    public static function sMembers(string $key): array
    {
        return self::slave()->sMembers($key);
    }

    /**
     * 检查元素是否在集合中
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function sIsMember(string $key, $value): bool
    {
        return self::slave()->sIsMember($key, $value);
    }

    // ==================== 有序集合操作 ====================

    /**
     * 添加有序集合元素
     * @param string $key
     * @param float $score
     * @param mixed $value
     * @return int
     */
    public static function zAdd(string $key, float $score, $value): int
    {
        return self::master()->zAdd($key, $score, $value);
    }

    /**
     * 获取有序集合元素按分数排序
     * @param string $key
     * @param int $start
     * @param int $end
     * @param bool $withScores
     * @return array
     */
    public static function zRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array
    {
        return self::slave()->zRange($key, $start, $end, $withScores);
    }

    // ==================== 事务操作 ====================

    /**
     * 开始事务
     * @return \Redis
     */
    public static function multi()
    {
        return self::master()->multi();
    }

    /**
     * 执行事务
     * @return array
     */
    public static function exec(): array
    {
        return self::master()->exec();
    }

    // ==================== 服务器操作 ====================

    /**
     * 清空当前数据库
     * @return bool
     */
    public static function flushDB(): bool
    {
        return self::master()->flushDB();
    }

    /**
     * 获取Redis服务器信息
     * @return array
     */
    public static function info(): array
    {
        return self::slave()->info();
    }
}