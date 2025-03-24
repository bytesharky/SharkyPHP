<?php

namespace Sharky\Core\Redis;

class Pool{
    private $master = null;
    private $slave = null;
    private $sticky = null;
    private $config = null;
    private $prefix = '';
    private $database = 0;

    public function __construct($config)
    {
        $this->config = $config;
    }

    private function connect($host, $port, $password = null, $timeout = 0): Connect
    {
        return new Connect($host, $port, $password, $timeout);
    }

    public function setSticky($sticky): Connect
    {
        $this->sticky = $sticky;
        return $sticky;
    }

    public function select($db) {
        $this->database = $db;  
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
            new \Exception("数据库配置master不存在");
        }

        $this->slave = $this->connect(
            $host,
            $this->config['port'],
            $this->config['password'],
            $this->config['timeout']
        );
        
        return $this->slave
            ->prefix($this->prefix)
            ->select($this->database);
    }   

    public function master(): Connect
    {
        if (isset($this->config['master'])) {
            $host = $this->config['master'];
        } else {
            new \Exception("数据库配置master不存在");
        }

        $this->master = $this->connect(
            $host,
            $this->config['port'],
            $this->config['password'],
            $this->config['timeout']
        );

        return $this->master
            ->prefix($this->prefix)
            ->select($this->database);
    }

    public function prefix($prefix) {
        $this->prefix = $prefix;
        return $this;
    }
}
