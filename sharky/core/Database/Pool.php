<?php

namespace Sharky\Core\Database;

class Pool{
    private $master = null;
    private $slave = null;
    private $sticky = null;
    private $config = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    private function connect($host, $user, $pass, $name, $port, $charset): Connect
    {
        return new Connect($host, $user, $pass, $name, $port, $charset);
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
            new \Exception("数据库配置master不存在");
        }

        $this->slave = $this->connect(
            $host,
            $this->config['username'],
            $this->config['password'],
            $this->config['database'],
            $this->config['port'],
            $this->config['charset']
        );
        
        return $this->slave;
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
            $this->config['username'],
            $this->config['password'],
            $this->config['database'],
            $this->config['port'],
            $this->config['charset']
        );

        return $this->master;
    }
}