<?php

/**
 * @description 控制器基类
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace App\Controllers;

use Sharky\Core\Controller as BaseController;

class Controller extends BaseController
{
    // 如果不需要使用模板引擎可以删除此函数
    public function render($name, array $context = []): string
    {
        if (class_exists("Sharky\\Libs\\Template")) {
            $template = new \Sharky\Libs\Template();
            return $template->render($name, $context);
        } else {
            $templateDir = $this->config->get("config.template.path");
            $templatePath = $templateDir . DIRECTORY_SEPARATOR . $name;
            if (!file_exists($templatePath)) {
                die("模板文件不存在: $name");
            }

            ob_start();
            extract($context);
            include $templatePath;
            return ob_get_clean();
        }
    }

    // 如果不需要使用模板引擎可以删除此函数
    public function display($name, array $context = []): void
    {
        echo ($this->render($name, $context));
    }
}
