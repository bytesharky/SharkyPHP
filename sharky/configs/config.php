<?php

/**
 * @description 基础配置文件
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

return [
    'isdebug'           => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'multi_site'        => filter_var(env('APP_MULTI_SITE', false), FILTER_VALIDATE_BOOLEAN),
    'restful'           => env('APP_RESTFUL', 'html'),
];
