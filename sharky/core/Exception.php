<?php
namespace Sharky\Core;

use \Exception as BaseException;

class Exception extends BaseException{

    private static $isInit = false;

    public static function init(){
        
        if (self::$isInit) return;

        set_exception_handler(array(self::class, 'unityExceptionHandler'));
        set_error_handler(array(self::class, 'unityErrorHandler'));

        // 加载配置
        $container = Container::getInstance();
        $config = $container->make('config');
        $isdebug = $config->get('config.isdebug', false);

        if ($isdebug) {
            // 开启调试
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
        } else {
            // 恢复默认
            // ini_restore('display_errors');
            // ini_restore('error_reporting');
            // 关闭错输出
            ini_set('display_errors', 0);
            ini_set('error_reporting', 0);
        }  
        self::$isInit = true;
    }

    // 统一处理异常和错误信息
    public static function unityExceptionHandler($exception)
    {
        self::init();
        // 调试打开时显示错误信息和堆栈跟踪
        $errorMessage = $exception->getMessage();
        $traceStr = $exception->getTraceAsString();
        self::renderError($errorMessage, $traceStr);
    }

    // 将[错误]转为[异常]
    public static function unityErrorHandler($errno, $errstr, $errfile, $errline)
    {
        self::init();
        // 调试打开时显示错误信息和堆栈跟踪
        $errorMessage = "级别: $errno" . PHP_EOL . "信息: $errstr" . PHP_EOL . "文件: $errfile" . PHP_EOL . "行号: $errline";
        $backtrace = debug_backtrace();
        $traceStr = '';
        foreach ($backtrace as $index => $trace) {
            $file = isset($trace['file']) ? $trace['file'] : '[internal function]';
            $line = isset($trace['line']) ? $trace['line'] : 'N/A';
            $function = isset($trace['function']) ? $trace['function'] : 'N/A';
            $class = isset($trace['class']) ? $trace['class'] : '';
            // 处理格式
            $traceStr .= "#$index $class$function() $file:$line" . PHP_EOL;
        }

        self::renderError($errorMessage, $traceStr);
    }

    // 输出错误页面
    private static function renderError($message, $traceStr)
    {
        if (!ini_get('display_errors')) {
            // 调试关闭时显示友好错误页面
            $template = SHARKY_ROOT . '/errors/friendly.php';
        } else {
            $template = SHARKY_ROOT . '/errors/debug.php';
        }

        if (file_exists($template)) {
            extract(['message' => $message, 'traceStr' => $traceStr]);
            include $template;
        } else {
            echo ('模板文件不存在!');
        }
        die();
    }
}