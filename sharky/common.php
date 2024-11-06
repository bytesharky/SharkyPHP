<?php

/**
 * @description 公共函数文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

// 多站点支持函数，不可删除
function getSitePath(){
    $container = Sharky\Core\Container::getInstance();
    $config = $container->make("config");
    $multiSite = $config->get("config.multi_site");
    if($multiSite){
        $sitesCfg = $config->get("sites");
        $mainDomain = $sitesCfg["domain"];
        $pattern = "/.?{$mainDomain}$/";
        $domain = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
        $subDomain = preg_replace($pattern, '', $domain);

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
                Sharky\Core\Exception::unityExceptionHandler(new \Exception("您已开启多站点模式，但是没有可用的站点，请检查配置文件"));
                die();
            }
        }
    }else{
        return APP_ROOT;
    }
}

function getCopyright($start, $copyright){
    $year = date("Y");
    $range = (($year > $start) ? implode('-', range($start, $year)) : $start);
    return  "Copyright © {$range} {$copyright} All rights reserved.";
}
