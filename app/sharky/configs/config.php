<?php

/**
 * @description 基础配置文件
 * @author Sharky
 * @date 2025-4-23
 * @version 1.3.0
 */

return [
    'restful'           => "html",
    'isdebug'           => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'template'          => [
        'path'          => 'views',
        'cache'         => 'caches',
    ],
    'language'          => [
        'default'       => 'en',
        'path'          => 'languages',
    ]
];
