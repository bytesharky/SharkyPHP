<?php
namespace Sharky\Core;

use \Exception as BaseException;

class Exception extends BaseException{

    private static $isInit = false;

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
        $isdebug = $config->get('config.isdebug', false);

        if ($isdebug) {
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
        self::init();
        // 调试打开时显示错误信息和堆栈跟踪
        $errorMessage = $exception->getMessage();
        $traceStr = $exception->getTraceAsString();
        self::renderError($errorMessage, $traceStr);
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

        self::renderError($errorMessage, $traceStr);
    }

    // 输出错误页面
    private static function renderError($message, $traceStr)
    {
        http_response_code(500);
        // 调试关闭时显示友好错误页面,否则输出详细信息
        if (!ini_get('display_errors')) {
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
