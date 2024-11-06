<?php

/**
 * @description 配置管理器模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

use Sharky\Utils\ArrayUtils;

class Config
{
    private $coreConfig = SHARKY_ROOT . "/configs";
  
    private $configs = [];

    public function __construct()
    {
        $this->initConfigs();
    }

    // 加载框架核心配置文件
    protected function initConfigs()
    {
        if (!is_dir($this->coreConfig)) {
            throw new \Exception("核心配置目录不存在");
        }
        $this->configs = $this->loadConfigByDir($this->coreConfig);
    }

    // 加载站点配置目录
    public function loadConfigs()
    {
        $siteConfig =  getSitePath()."/configs";
        if (!is_dir($siteConfig)) {
            throw new \Exception("站点配置目录不存在");
        }
        $siteConfig = $this->loadConfigByDir($siteConfig);
        $this->configs = ArrayUtils::deepMerge($this->configs, $siteConfig);
    }

    // 加载指定目录的配置文件并返回数据
    private function loadConfigByDir($directory)
    {
        // 目录不存在时返回空数组
        if (!is_dir($directory)) {
            return [];
        }

        $data = [];
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $filePath = $directory . '/' . $file;
                // 加载配置文件并获取其返回的数组内容
                $configData = require $filePath;
                // 将不包含扩展名的文件名作为键，配置内容作为值存入数组
                $data[pathinfo($file, PATHINFO_FILENAME)] = $configData;
            }
        }
        // 返回加载的配置数据
        return $data;
    }

    // 根据点分字符串读取配置值
    public function get($path, $default = null)
    {
        return ArrayUtils::get($this->configs, $path, $default);
    }
}
