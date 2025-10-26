<?php

/**
 * @description 数据库模块
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

use Sharky\Core\Database\Pool;
use Sharky\Core\Database\Connect;
use Sharky\Exceptions\ConfigNotFoundException;

class Database
{
    private static $connects = [];
    private static $config = null;

    private static function loadConfig(): mixed
    {
        if (self::$config) {
            return self::$config;
        }

        self::$config = Container::getInstance()->make('config')->get('database');

        if (empty(self::$config)) {
            throw new ConfigNotFoundException("Mysql 数据库配置不存在");
        }

        return self::$config;
    }

    public static function connects($database = 'default'): Pool
    {
        if (isset(self::$connects[$database])) {
            return self::$connects[$database];
        }
        $config = self::loadConfig();

        if (!isset($config[$database])) {
            new ConfigNotFoundException("Mysql 数据库配置 {$database} 不存在");
        }

        $connect = new Pool($config[$database]);

        self::$connects[$database] = $connect;

        return $connect;
    }

    public static function slave(): Connect
    {
        return self::connects()->slave();
    }

    public static function master(): Connect
    {
        return self::connects()->master();
    }

    public static function query($sql, $params = []): mixed
    {
        return self::slave()->query($sql, $params);
    }

    public static function execute($sql, $params = [])
    {
        $config = self::loadConfig();
        if (isset($config['sticky']) && $config['sticky']) {
            self::connects()->setSticky(self::master());
        }
        return self::master()->execute($sql, $params);
    }

    public static function getFields($table): array
    {
        return self::slave()->getFields($table);
    }

    public static function beginTransaction(): bool
    {
        self::connects()->setSticky(self::master());
        return self::master()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::master()->commit();
    }

    public static function rollback(): bool
    {
        return self::master()->rollback();
    }

    public static function runTransaction(callable $callBack): mixed
    {
        self::connects()->setSticky(self::master());
        return self::master()->runTransaction($callBack);
    }

    public static function lastInsertId(): bool|int|string
    {
        return self::master()->lastInsertId();
    }

    public static function table(string $table): Model
    {
        return new Model($table);
    }

}
