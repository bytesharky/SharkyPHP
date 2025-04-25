<?php

/**
 * @description 基础配置文件
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

return [
    'restful'           => "html",
    'isdebug'           => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'template'          => [
        'path'          => 'views',
        'cache'         => 'caches',
    ],
    'cache'             => [
        'type'          => 'file',
        'path'          => 'caches',
        'expire'        => 3600,
    ],
    'language'          => [
        'default'       => 'en',
        'path'          => 'languages',
    ]
];
