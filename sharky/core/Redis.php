<?php

/**
 * @description Redis数据库操作类
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

use Sharky\Core\Redis\Pool;
use Sharky\Core\Redis\Connect;
use Sharky\Exceptions\ConfigNotFoundException;

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

        self::$config = Container::getInstance()->make('config')->get('redis', []);

        if (empty(self::$config)) {
            throw new ConfigNotFoundException("Redis 数据库配置不存在");
        }

        return self::$config;
    }

    /**
     * 获取Redis连接池实例
     * @param string $name 配置名称
     * @return Pool
     */
    public static function connects(string $name = 'default'): Pool
    {
        if (isset(self::$connects[$name])) {
            return self::$connects[$name];
        }

        $config = self::loadConfig();

        if (!isset($config[$name])) {
            throw new ConfigNotFoundException("Redis 数据配置 {$name} 不存在");
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
     */
    public static function slave(): Connect
    {
        return self::connects()->slave();
    }

    /**
     * 获取主库连接
     * @return Connect
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

    // ==================== 基本键操作 ====================

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
    public static function get(string $key): mixed
    {
        return self::slave()->get($key);
    }

    /**
     * 删除键
     * @param string|array $key
     * @return int
     */
    public static function delete(string $key): int
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
     * 移除键的过期时间
     * @param string $key 键名
     * @return bool
     */
    public function persist(string $key)
    {
        return self::master()->persist($key);
    }

    // ==================== 字符串操作 ====================

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

    /**
     * 值自增
     * @param string $key 键名
     * @param int $increment 增量
     * @return int 自增后的值
     */
    public static function incr(string $key, int $increment = 1)
    {
        return self::master()->incr($key, $increment);
    }

    /**
     * 值自减
     * @param string $key 键名
     * @param int $decrement 减量
     * @return int 自减后的值
     */
    public static function decr(string $key, int $decrement = 1)
    {
        return self::master()->decr($key, $decrement);
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

    /**
     * 删除哈希字段
     * @param string $key 键名
     * @param string|array $fields 字段名或字段名数组
     * @return int 删除的字段数量
     */
    public static function hDel(string $key, array|string $fields)
    {
        return self::master()->hDel($key, $fields);
    }

    /**
     * 哈希字段自增
     * @param string $key 键名
     * @param string $field 字段名
     * @param int $increment 增量
     * @return int 自增后的值
     */
    public static function hIncrBy(string $key, string $field, int $increment = 1)
    {
        return self::master()->hIncrBy($key, $field, $increment);
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

    /**
     * 获取列表片段
     * @param string $key 键名
     * @param int $start 起始索引
     * @param int $end 结束索引
     * @return array
     */
    public function lRange(string $key, int $start = 0, int $end = -1)
    {
        return self::slave()->lRange($key, $start, $end);
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
     * 删除集合元素
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 删除的元素数量
     */
    public function sRem(string $key, $value)
    {
        return self::master()->sRem($key, $value);
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

    /**
     * 获取有序集合元素按分数逆序排序
     * @param string $key 键名
     * @param int $start 起始排名
     * @param int $end 结束排名
     * @param bool $withScores 是否返回分数
     * @return array
     */
    public function zRevRange(string $key, int $start = 0, int $end = -1, bool $withScores = false)
    {
        return self::slave()->zRevRange($key, $start, $end, $withScores);
    }

    // ==================== 事务操作 ====================

    /**
     * 开始事务
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

    /**
     * 取消事务
     * @return bool
     */
    public function discard()
    {
        return self::master()->discard();
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
     * 清空所有数据库
     * @return bool
     */
    public function flushAll()
    {
        return self::master()->flushAll();
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