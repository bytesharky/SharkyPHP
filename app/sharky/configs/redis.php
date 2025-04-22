<?php

/**
 * @description Redis配置文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

return [
    'default'  => [
        // 如果不使用读写分离，只需配置master即可
        'master'     =>  env('REDIS_MASTER', 'localhost'),
        'slave'      =>  env('REDIS_SLAVE', 'localhost') ,
        'port'       =>  env('REDIS_PORT', 6379),
        'password'   =>  env('REDIS_PASS', ''),
        'timeout'    =>  env('REDIS_TIMEOUT', 0),
        'database'   =>  env('REDIS_DB', 0),
        'prefix'     =>  env('REDIS_PREFIX', 'sharky_')
    ]
 ];

