<?php

/**
 * @description Redis数据库操作类
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

use Sharky\Core\Redis\Pool;

class Redis {
    private static $config = null;
    private static $connects = [];
 
    private static function loadConfig()
    {
        if (self::$config) {
            return self::$config;
        }

        self::$config = Container::getInstance()->make('config')->get('redis');

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

    public static function prefix($prefix) {
        return self::connects()->prefix($prefix);
    }

    public static function select($db) {
        return self::connects()->select($db);    
    }

    public static function set($key, $value) {
        $config = self::loadConfig();
        if (isset($config['sticky']) && $config['sticky']) {
            self::connects()->setSticky(self::master());
        }
        return self::connectS()->master()->set($key, $value);
    }

    public static function get($key) {
        return self::connects()->slave()->get($key);
    }

    public static function delete($key) {
        $config = self::loadConfig();
        if (isset($config['sticky']) && $config['sticky']) {
            self::connects()->setSticky(self::master());
        }
        return self::connects()->master()->delete($key);
    }

    public static function exists($key) {
        return self::connects()->slave()->exists($key);
    }

}

