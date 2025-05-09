<?php

/**
 * @description 基础配置文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

return [
    'isdebug'           => env('APP_DEBUG', false),
    'template'          => [
        'path'          => 'views',
        'cache'         => 'caches',
    ],
    'language'          => 'languages',
];
