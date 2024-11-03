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
        $loader = new \Twig\Loader\FilesystemLoader('../app/views');
        $this->twig = new \Twig\Environment($loader);
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
