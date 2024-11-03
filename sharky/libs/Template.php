<?php

/**
 * @description 微模版引擎
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 *
 * 此模板引擎相当简单，不建议使用
 * 可作为开发API时的一个辅助
 * 如果不使用可以删除
 */

namespace Sharky\Libs;

class Template
{
    protected $templateDir;
    public function __construct($templateDir = APP_ROOT . '/views')
    {
        $this->templateDir = $templateDir;
    }


    public function render($name, array $context = []): string
    {
        $templatePath = "{$this->templateDir}/{$name}";
        if (!file_exists($templatePath)) {
            die("模板文件不存在: $name");
        }

        ob_start();
        extract($context);
        include $templatePath;
        return ob_get_clean();
    }

    public function display($name, array $context = []): void
    {
        $content = $this->render($name, $context);
        echo($content);
    }
}
