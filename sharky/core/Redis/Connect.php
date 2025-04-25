<?php

/**
 * @description Redis数据库实例类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core\Redis;

class Connect {
    private $redis = null;
    private $prefix = 'sharky_';
    private $name = 'default';

    private $host = null;
    private $port = null;
    private $password = null;
    private $timeout = 0;
    private $persistent = false;
    private $persistentId = null;

    /**
     * 构造函数
     * @param string $host Redis服务器地址
     * @param int $port Redis服务器端口
     * @param string|null $password Redis密码
     * @param int $timeout 连接超时时间(秒)
     * @param bool $persistent 是否使用持久连接
     * @param string|null $persistentId 持久连接ID
     */
    public function __construct($host, $port, $password = null, $timeout = 0, $persistent = false, $persistentId = null) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->persistent = $persistent;
        $this->persistentId = $persistentId;
    }

    /**
     * 连接到Redis服务器
     * @throws \RedisException
     */
    private function connect() {
        $this->redis = new \Redis();
        
        if ($this->persistent) {
            $this->redis->pconnect($this->host, $this->port, $this->timeout, $this->persistentId);
        } else {
            $this->redis->connect($this->host, $this->port, $this->timeout);
        }
        
        if ($this->password) {
            $this->redis->auth($this->password);
        }
    }

    /**
     * 检查并确保连接
     */
    private function ensureConnection() {
        if (!$this->redis) {
            $this->connect();
        }
    }

    /**
     * 设置键前缀
     * @param string $prefix 键前缀
     * @return $this
     */
    public function prefix($prefix) {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 选择数据库
     * @param int $db 数据库索引
     * @return $this
     */
    public function select($db) {
        $this->ensureConnection();
        $this->redis->select($db);
        return $this;
    }

    // ==================== 基本键操作 ====================

    /**
     * 设置键值
     * @param string $key 键名
     * @param mixed $value 值
     * @param int|null $ttl 过期时间(秒)
     * @return bool
     */
    public function set($key, $value, $ttl = null) {
        $this->ensureConnection();
        $prefixedKey = $this->prefix . $key;
        
        if ($ttl !== null) {
            return $this->redis->setex($prefixedKey, $ttl, $value);
        }
        return $this->redis->set($prefixedKey, $value);
    }

    /**
     * 获取键值
     * @param string $key 键名
     * @return mixed
     */    public function get($key) {
        $this->ensureConnection();

        if ($this->exists($key) === false) {
            return null;
        }
        return $this->redis->get($this->prefix . $key);
    }

    /**
     * 删除键
     * @param string|array $key 键名或键名数组
     * @return int 删除的键数量
     */
    public function delete($key) {
        $this->ensureConnection();
        
        if (is_array($key)) {
            $prefixedKeys = array_map(function($k) {
                return $this->prefix . $k;
            }, $key);
            return $this->redis->del($prefixedKeys);
        }
        
        return $this->redis->del($this->prefix . $key);
    }

    /**
     * 检查键是否存在
     * @param string $key 键名
     * @return bool
     */
    public function exists($key) {
        $this->ensureConnection();
        $exists = $this->redis->exists($this->prefix . $key);
        return (bool)($exists);
    }

    /**
     * 设置键的过期时间
     * @param string $key 键名
     * @param int $ttl 过期时间(秒)
     * @return bool
     */
    public function expire($key, $ttl) {
        $this->ensureConnection();
        return $this->redis->expire($this->prefix . $key, $ttl);
    }

    /**
     * 设置键在指定时间戳过期
     * @param string $key 键名
     * @param int $timestamp 过期时间戳
     * @return bool
     */
    public function expireAt($key, $timestamp) {
        $this->ensureConnection();
        return $this->redis->expireAt($this->prefix . $key, $timestamp);
    }

    /**
     * 获取键的剩余生存时间
     * @param string $key 键名
     * @return int 剩余时间(秒), -1表示没有设置过期时间, -2表示键不存在
     */
    public function ttl($key) {
        $this->ensureConnection();
        return $this->redis->ttl($this->prefix . $key);
    }

    /**
     * 移除键的过期时间
     * @param string $key 键名
     * @return bool
     */
    public function persist($key) {
        $this->ensureConnection();
        return $this->redis->persist($this->prefix . $key);
    }

    // ==================== 字符串操作 ====================

    /**
     * 键不存在时设置值
     * @param string $key 键名
     * @param mixed $value 值
     * @return bool
     */
    public function setnx($key, $value) {
        $this->ensureConnection();
        return $this->redis->setnx($this->prefix . $key, $value);
    }

    /**
     * 批量设置键值
     * @param array $items 键值对数组
     * @return bool
     */
    public function mset(array $items) {
        $this->ensureConnection();
        $prefixedItems = [];
        foreach ($items as $k => $v) {
            $prefixedItems[$this->prefix . $k] = $v;
        }
        return $this->redis->mset($prefixedItems);
    }

    /**
     * 批量获取键值
     * @param array $keys 键名数组
     * @return array
     */
    public function mget(array $keys) {
        $this->ensureConnection();
        $prefixedKeys = array_map(function($k) {
            return $this->prefix . $k;
        }, $keys);
        return $this->redis->mget($prefixedKeys);
    }

    /**
     * 值自增
     * @param string $key 键名
     * @param int $increment 增量
     * @return int 自增后的值
     */
    public function incr($key, $increment = 1) {
        $this->ensureConnection();
        if ($increment == 1) {
            return $this->redis->incr($this->prefix . $key);
        }
        return $this->redis->incrBy($this->prefix . $key, $increment);
    }

    /**
     * 值自减
     * @param string $key 键名
     * @param int $decrement 减量
     * @return int 自减后的值
     */
    public function decr($key, $decrement = 1) {
        $this->ensureConnection();
        if ($decrement == 1) {
            return $this->redis->decr($this->prefix . $key);
        }
        return $this->redis->decrBy($this->prefix . $key, $decrement);
    }

    // ==================== 哈希操作 ====================

    /**
     * 设置哈希字段值
     * @param string $key 键名
     * @param string $field 字段名
     * @param mixed $value 值
     * @return int 1-新字段被设置, 0-已有字段被更新
     */
    public function hSet($key, $field, $value) {
        $this->ensureConnection();
        return $this->redis->hSet($this->prefix . $key, $field, $value);
    }

    /**
     * 获取哈希字段值
     * @param string $key 键名
     * @param string $field 字段名
     * @return mixed
     */
    public function hGet($key, $field) {
        $this->ensureConnection();
        return $this->redis->hGet($this->prefix . $key, $field);
    }

    /**
     * 批量设置哈希字段
     * @param string $key 键名
     * @param array $fields 字段值对数组
     * @return bool
     */
    public function hMSet($key, array $fields) {
        $this->ensureConnection();
        return $this->redis->hMSet($this->prefix . $key, $fields);
    }

    /**
     * 批量获取哈希字段
     * @param string $key 键名
     * @param array $fields 字段名数组
     * @return array
     */
    public function hMGet($key, array $fields) {
        $this->ensureConnection();
        return $this->redis->hMGet($this->prefix . $key, $fields);
    }

    /**
     * 获取所有哈希字段和值
     * @param string $key 键名
     * @return array
     */
    public function hGetAll($key) {
        $this->ensureConnection();
        return $this->redis->hGetAll($this->prefix . $key);
    }

    /**
     * 删除哈希字段
     * @param string $key 键名
     * @param string|array $fields 字段名或字段名数组
     * @return int 删除的字段数量
     */
    public function hDel($key, $fields) {
        $this->ensureConnection();
        return $this->redis->hDel($this->prefix . $key, ...(array)$fields);
    }

    /**
     * 哈希字段自增
     * @param string $key 键名
     * @param string $field 字段名
     * @param int $increment 增量
     * @return int 自增后的值
     */
    public function hIncrBy($key, $field, $increment = 1) {
        $this->ensureConnection();
        return $this->redis->hIncrBy($this->prefix . $key, $field, $increment);
    }

    // ==================== 列表操作 ====================

    /**
     * 左推入列表
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 列表长度
     */
    public function lPush($key, $value) {
        $this->ensureConnection();
        return $this->redis->lPush($this->prefix . $key, $value);
    }

    /**
     * 右推入列表
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 列表长度
     */
    public function rPush($key, $value) {
        $this->ensureConnection();
        return $this->redis->rPush($this->prefix . $key, $value);
    }

    /**
     * 左弹出列表
     * @param string $key 键名
     * @return mixed
     */
    public function lPop($key) {
        $this->ensureConnection();
        return $this->redis->lPop($this->prefix . $key);
    }

    /**
     * 右弹出列表
     * @param string $key 键名
     * @return mixed
     */
    public function rPop($key) {
        $this->ensureConnection();
        return $this->redis->rPop($this->prefix . $key);
    }

    /**
     * 获取列表长度
     * @param string $key 键名
     * @return int
     */
    public function lLen($key) {
        $this->ensureConnection();
        return $this->redis->lLen($this->prefix . $key);
    }

    /**
     * 获取列表片段
     * @param string $key 键名
     * @param int $start 起始索引
     * @param int $end 结束索引
     * @return array
     */
    public function lRange($key, $start = 0, $end = -1) {
        $this->ensureConnection();
        return $this->redis->lRange($this->prefix . $key, $start, $end);
    }

    // ==================== 集合操作 ====================

    /**
     * 添加集合元素
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 添加的元素数量
     */
    public function sAdd($key, $value) {
        $this->ensureConnection();
        return $this->redis->sAdd($this->prefix . $key, $value);
    }

    /**
     * 删除集合元素
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 删除的元素数量
     */
    public function sRem($key, $value) {
        $this->ensureConnection();
        return $this->redis->sRem($this->prefix . $key, $value);
    }

    /**
     * 获取所有集合成员
     * @param string $key 键名
     * @return array
     */
    public function sMembers($key) {
        $this->ensureConnection();
        return $this->redis->sMembers($this->prefix . $key);
    }

    /**
     * 检查元素是否在集合中
     * @param string $key 键名
     * @param mixed $value 值
     * @return bool
     */
    public function sIsMember($key, $value) {
        $this->ensureConnection();
        return $this->redis->sIsMember($this->prefix . $key, $value);
    }

    // ==================== 有序集合操作 ====================

    /**
     * 添加有序集合元素
     * @param string $key 键名
     * @param float $score 分数
     * @param mixed $value 值
     * @return int 添加的元素数量
     */
    public function zAdd($key, $score, $value) {
        $this->ensureConnection();
        return $this->redis->zAdd($this->prefix . $key, $score, $value);
    }

    /**
     * 获取有序集合元素按分数排序
     * @param string $key 键名
     * @param int $start 起始排名
     * @param int $end 结束排名
     * @param bool $withScores 是否返回分数
     * @return array
     */
    public function zRange($key, $start = 0, $end = -1, $withScores = false) {
        $this->ensureConnection();
        return $this->redis->zRange($this->prefix . $key, $start, $end, $withScores);
    }

    /**
     * 获取有序集合元素按分数逆序排序
     * @param string $key 键名
     * @param int $start 起始排名
     * @param int $end 结束排名
     * @param bool $withScores 是否返回分数
     * @return array
     */
    public function zRevRange($key, $start = 0, $end = -1, $withScores = false) {
        $this->ensureConnection();
        return $this->redis->zRevRange($this->prefix . $key, $start, $end, $withScores);
    }

    // ==================== 事务操作 ====================

    /**
     * 开始事务
     * @return \Redis
     */
    public function multi() {
        $this->ensureConnection();
        return $this->redis->multi();
    }

    /**
     * 执行事务
     * @return array
     */
    public function exec() {
        $this->ensureConnection();
        return $this->redis->exec();
    }

    /**
     * 取消事务
     * @return bool
     */
    public function discard() {
        $this->ensureConnection();
        return $this->redis->discard();
    }

    // ==================== 其他操作 ====================

    /**
     * 清空当前数据库
     * @return bool
     */
    public function flushDB() {
        $this->ensureConnection();
        return $this->redis->flushDB();
    }

    /**
     * 清空所有数据库
     * @return bool
     */
    public function flushAll() {
        $this->ensureConnection();
        return $this->redis->flushAll();
    }

    /**
     * 获取Redis服务器信息
     * @return array
     */
    public function info() {
        $this->ensureConnection();
        return $this->redis->info();
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        if ($this->redis && !$this->persistent) {
            $this->redis->close();
            $this->redis = null;
        }
    }
}