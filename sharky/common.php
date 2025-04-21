<?php

/**
 * @description 公共函数文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

// 获取站点路径
function getSitePath(){
    $container = Sharky\Core\Container::getInstance();
    $config = $container->make("config");
    $multiSite = $config->get("config.multi_site");
    if($multiSite){
        $sitesCfg = $config->get("sites");
        $mainDomain = $sitesCfg["domain"];
        $pattern = "/.?{$mainDomain}$/";
        $domain = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
        $subDomain = preg_replace($pattern, '', $domain??"");

        if (in_array($domain , array_keys($sitesCfg["sites"])) ){
            return $sitesCfg["sites"][$domain];
        }elseif (in_array($subDomain , array_keys($sitesCfg["sites"])) ){
            return $sitesCfg["sites"][$subDomain];
        }else{
            if (isset($sitesCfg["default"])){
                if (isset($sitesCfg["sites"][$sitesCfg["default"]])){
                    return APP_ROOT. DIRECTORY_SEPARATOR . $sitesCfg["sites"][$sitesCfg["default"]];
                }
            }
            if (!empty($sitesCfg["sites"])){
                return APP_ROOT. DIRECTORY_SEPARATOR . reset($sitesCfg["sites"]);
            } else {
                Sharky\Core\SharkyException::unityExceptionHandler(new \Exception("您已开启多站点模式，但是没有可用的站点，请检查配置文件"));
                die();
            }
        }
    }else{
        return APP_ROOT;
    }
}

// 多站点自动加载函数
function autoloadClasses($className){
    $paths = explode("\\", $className);
    array_shift($paths);
    $className = array_pop($paths);
    $classPath = strtolower(implode(DIRECTORY_SEPARATOR, $paths));

    // 将命名空间转换为路径
    $classFile = implode(DIRECTORY_SEPARATOR, [SITE_ROOT,$classPath, $className]) . ".php";

    // 如果文件存在，加载该类文件
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}

// 获取版权信息
function getCopyright($start, $copyright){
    $year = date("Y");
    $range = (($year > $start) ? implode('-', range($start, $year)) : $start);
    return  "Copyright © {$range} {$copyright} All rights reserved.";
}

// 获取环境变量
function env($key, $default = null) {
    return (array_key_exists($key, $_ENV) ? $_ENV[$key] : getenv($key)) ?: $default;
}

// 加载环境变量
function loadEnv($filePath = '../.env') {

    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}
