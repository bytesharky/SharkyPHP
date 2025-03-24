<?php

namespace Sharky\Core;

use Sharky\Core\Database\Pool;

class Database
{
    private static $connects = [];
    private static $config = null;

    private static function loadConfig()
    {
        if (self::$config) {
            return self::$config;
        }

        self::$config = Container::getInstance()->make('config')->get('database');

        return self::$config;
    }

    public static function connects($name = 'default'): Pool
    {
        if (isset(self::$connects[$name])) {
            return self::$connects[$name];
        }
        $config = self::loadConfig();
        
        if (!isset($config[$name])) {
            new \Exception("数据库配置不存在");
        }

        $connect = new Pool($config[$name]);

        self::$connects[$name] = $connect;

        return $connect;
    }

    public static function slave()
    {
        return self::connects()->slave();
    }   

    public static function master()
    {
        return self::connects()->master();
    }

    public static function query($sql, $params = [])
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

    public static function getFields($table)
    {
        return self::slave()->getFields($table);
    }

    public static function beginTransaction()
    {
        return self::master()->beginTransaction();
    }

    public static function commit()
    {
        $config = self::loadConfig();
        if (isset($config['sticky']) && $config['sticky']) {
            self::connects()->setSticky(self::master());
        }
        return self::master()->commit();
    }

    public static function rollback()
    {
        return self::master()->rollback();
    }

    public static function runTransaction(callable $callBack){
        try {
            self::beginTransaction();

            if ($callBack(self::master())) {
                self::commit();
                return true;
            } else {
                self::rollback();
                return false;
            }
        } catch (\Exception $e) {
            self::rollback();
            return false;
        }
    }

    public static function lastInsertId()
    {
        return self::master()->lastInsertId();
    }

}
