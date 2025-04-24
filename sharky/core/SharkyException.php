<?php

/**
 * @description 异常类
 * @author Sharky
 * @date 2025-4-25
 * @version 1.3.1
 */

namespace Sharky\Core;

use Exception;

class RouteNotFoundException extends SharkyException{}
class SharkyException extends Exception{

    private static $isInit = false;

    private static $isDebug;

    public static function init(){
        
        if (self::$isInit) return;

        set_exception_handler(array(self::class, 'unityExceptionHandler'));
        set_error_handler(array(self::class, 'unityErrorHandler'));

        self::recover();

        self::$isInit = true;
    }

    // 根据配置文件重新
    public static function recover(){
        // 加载配置
        $container = Container::getInstance();
        $config = $container->make('config');
        self::$isDebug = $config->get('config.isdebug', false);

        if (self::$isDebug) {
            self::display() ;
        } else {
            self::hidden() ;
        }  
    }

    public static function display(){
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
    }

    public static function hidden(){
        ini_set('display_errors', 0);
        ini_set('error_reporting', 0);
    }

    // 统一处理异常信息
    public static function unityExceptionHandler($exception)
    {
        if (!self::$isInit) {
            self::init();
        }
        // 调试打开时显示错误信息和堆栈跟踪
        $errorMessage = $exception->getMessage();
        $traceStr = $exception->getTraceAsString();
        self::render($errorMessage, $traceStr);
    }

    // 统一处错误常信息
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

        self::render($errorMessage, $traceStr);
    }

    // 输出错误页面
    private static function render($message, $traceStr)
    {
        // 清空缓冲，以免之前的输出破坏错误页面
        ob_clean();
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(500);
        // 调试关闭时显示友好错误页面,否则输出详细信息
        if (!self::$isDebug) {
            $template = SHARKY_ROOT . '/errors/friendly.php';
        } else {
            $template = SHARKY_ROOT . '/errors/debug.php';
        }

        if (file_exists($template)) {
            extract(['message' => $message, 'traceStr' => $traceStr]);
            include $template;
        } else {
            if (!self::$isDebug) {
                echo ('<pre>遇到一个问题，请稍后再试!</pre>');            
            } else {
                echo ('<pre>异常跟踪模板文件不存在!</pre><pre>'. $message. '</pre><pre>'. $traceStr. '</pre>');
            }
        }
        die();
    }
}
