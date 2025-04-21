<?php

/**
 * @description 数据配置文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

return [
    'default'  => [
        // 如果不使用读写分离，只需配置master即可
        'master'        =>  env('DB_MASTER', 'localhost'),
        'slave'         =>  env('DB_SLAVE', 'localhost'),
        'sticky'        =>  env('DB_STICKY', true),
        'port'          =>  env('DB_PORT', 3306),
        'timeout'       =>  env('DB_TIMEOUT', 0),
        'prefix'        =>  env('DB_PREFIX', ''),
        'database'      =>  env('DB_DATABASE', 'sharky'),
        'username'      =>  env('DB_USER', 'root'),
        'password'      =>  env('DB_PASS', ''),
        'charset'       =>  env('DB_CHARSET', 'utf8mb4'),
    ]
];

