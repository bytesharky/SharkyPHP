<?php

/**
 * @description Redis数据库实例类
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core\Redis;

class Connect {
    private $redis = null;
    private $prefix = 'sharky_';
    private $name = 'default';

    private static $host = null;
    private static $port = null;
    private static $password = null;
    private static $timeout = 0;

    public function __construct($host, $port, $password = null, $timeout = 0) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    private function connect() {
        $this->redis = new \Redis();
        $this->redis->connect($this->host, $this->port, $this->timeout);
        if ($this->password) {
            $this->redis->auth($this->password);
        }
    }

    public function prefix($prefix) {
        $this->prefix = $prefix;
        return $this;
    }

    public function select($db) {
        if (!$this->redis) {
            $this->connect();
        }
        $this->redis->select($db);
        return $this;
    }

    public function set($key, $value) {
        if (!$this->redis) {
            $this->connect();
        }
        return $this->redis->set($this->prefix . $key, $value);
    }

    public function get($key) {
        if (!$this->redis) {
            $this->connect();
        }
        return $this->redis->get($this->prefix . $key);
    }

    public function delete($key) {
        if (!$this->redis) {
            $this->connect();
        }
        return $this->redis->del($this->prefix . $key);
    }

    public function exists($key) {
        if (!$this->redis) {
            $this->connect();
        }
        return $this->redis->exists($this->prefix . $key);
    }

    public function __destruct() {
        if ($this->redis) {
            $this->redis->close();
            $this->redis = null;
        }
    }
}
