<?php

/**
 * @description 数据库连接池
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core\Database;

use Sharky\Exceptions\ConfigNotFoundException;

class Pool
{
    private $master = null;
    private $slave = null;
    private $sticky = null;
    private $config = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    private function connect($host, $type, $user, $pass, $name, $port, $charset, $timeou): Connect
    {
        return new Connect($host, $type, $user, $pass, $name, $port, $charset, $timeou);
    }

    public function setSticky($sticky): Connect
    {
        $this->sticky = $sticky;
        return $sticky;
    }

    public function slave(): Connect
    {
        if ($this->sticky) {
            return $this->sticky;
        }

        if (isset($this->config['slave'])) {
            $host = $this->config['slave'];
        } else if (isset($this->config['master'])) {
            $host = $this->config['master'];
        } else {
            new ConfigNotFoundException("Mysql 从数据库配置 slave 不存在");
        }

        if (empty($this->slave)) {
            $this->slave = $this->connect(
                $host,
                $this->config['type'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database'],
                $this->config['port'],
                $this->config['charset'],
                $this->config['timeout']
            );
        }

        return $this->slave;
    }

    public function master(): Connect
    {
        if (isset($this->config['master'])) {
            $host = $this->config['master'];
        } else {
            new ConfigNotFoundException("Mysql 主数据库配置 master 不存在");
        }

        if (empty($this->master)) {
            $this->master = $this->connect(
                $host,
                $this->config['type'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database'],
                $this->config['port'],
                $this->config['charset'],
                $this->config['timeout']
            );
        }

        return $this->master;
    }
}