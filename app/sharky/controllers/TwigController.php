<?php

/**
 * @description 带视图的控制器
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace App\Controllers;

class TwigController extends Controller
{
    protected $twig;
    public function __construct()
    {
        if (class_exists('\Twig\Loader\FilesystemLoader')) {
            $loader = new \Twig\Loader\FilesystemLoader(implode(DIRECTORY_SEPARATOR, ["..", "app", "sharky", "views"]));
            $this->twig = new \Twig\Environment($loader);
        } else {

            $errmsg = "未安装Twig模版引擎\n" .
                "使用composer require twig/twig命令 安装\n" .
                "使用composer remove twig/twig命令 移除\n";

            throw new \Exception("$errmsg");
        }

    }

    public function render($name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }

    public function display($name, array $context = []): void
    {
        $this->twig->display($name, $context);
    }
}
