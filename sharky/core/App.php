<?php

/**
 * @description 框架核心
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

class App
{
    protected $config;
    protected $router;

    // 初始化
    public function __construct(Router $router, Config $config)
    {
        try {
            $this->config = $config;
            $this->router = $router;
            set_exception_handler(array('Sharky\Core\App', 'unityExceptionHandler'));
            set_error_handler(array('Sharky\Core\App', 'unityErrorHandler'));
            // 加载配置
            $configs = $config->get('config');
            if (!is_array($configs)) {
                die('缺少configs/config.php');
            } else {
                if ($configs['isdebug'] ?? false) {
                    // 开启调试
                    ini_set('display_errors', 1);
                    ini_set('error_reporting', E_ALL);
                } else {
                    // 恢复默认
                    ini_restore('display_errors');
                    ini_restore('error_reporting');
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    // 启动应用
    public function run()
    {
        // 派发路由
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $this->router->dispatch($method, $uri);
    }

    // 统一处理异常和错误信息
    public static function unityExceptionHandler($exception)
    {
        // 调试打开时显示错误信息和堆栈跟踪
        $errorMessage = $exception->getMessage();
        $traceStr = $exception->getTraceAsString();
        App::showError($errorMessage, $traceStr);
    }

    // 将[错误]转为[异常]
    public static function unityErrorHandler($errno, $errstr, $errfile, $errline)
    {
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

        App::showError($errorMessage, $traceStr);
    }

    // 输出错误页面
    public static function showError($message, $traceStr)
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
