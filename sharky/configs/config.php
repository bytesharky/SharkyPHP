<?php

/**
 * @description 基础配置文件
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

return [
    'isdebug'           => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'multi_site'        => filter_var(env('APP_MULTI_SITE', false), FILTER_VALIDATE_BOOLEAN),
    'restful'           => env('APP_RESTFUL', 'html'),
];
