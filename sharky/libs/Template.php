<?php

/**
 * @description 微模版引擎
 * @author Sharky
 * @date 2024-11-5
 * @version 1.0.0
 *
 */

namespace Sharky\Libs;

use Sharky\Core\Container;
USE Sharky\Utils\ArrayUtils;

class Template
{
    protected $templateDir;
    protected $cacheDir;
    protected $translations = [];

    public function __construct($lang = 'zh')
    {
        // 加载配置文件
        $container = Container::getInstance();
        $config = $container->make('config');

        // 视图路径和缓存路径
        $templateDir = SITE_ROOT . DIRECTORY_SEPARATOR . $config->get('config.template.path', 'views');
        $cacheDir = SITE_ROOT . DIRECTORY_SEPARATOR . $config->get('config.template.cache', 'caches');

        $this->templateDir = rtrim($templateDir, DIRECTORY_SEPARATOR);
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);

        // 加载多语言
        $langPath = $config->get('config.language.path');
        $defaultLang = $config->get('config.language.default');
        $defaultFile = implode(DIRECTORY_SEPARATOR, ["", SITE_ROOT, $langPath, $defaultLang, ".php"]) ;
        $userFile = implode(DIRECTORY_SEPARATOR, ["", SITE_ROOT, $langPath, $lang, ".php"]) ;
        // 默认语言
        if ($defaultFile && file_exists($defaultFile)) {
            $defaultTranslations = include $defaultFile;
        }
        // 用户选择语言
        if ($userFile && file_exists($userFile)) {
            $userTranslations = include $userFile;
        }

        $this->translations = ArrayUtils::deepMerge($defaultTranslations, $userTranslations);
    }

    protected function translate($key)
    {
        // 返回翻译文本，若没有则返回原键
        return $this->translations[$key] ?? $key;
    }

    public function render($template, $variables = [])
    {
        $compiledFile = $this->compile($template);
        extract($variables, EXTR_OVERWRITE);
        ob_start();
        include $compiledFile;
        return ob_get_clean();
    }

    protected function compile($template)
    {
        $template = preg_replace('/[\\\\\/]/',DIRECTORY_SEPARATOR, $template,);
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR. $template;
        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR . md5($template) . '.php';

        if (!file_exists($cachePath) || filemtime($cachePath) < filemtime($templatePath)) {
            if (!file_exists($templatePath)){
                new \Exception("模版文件{$templatePath}不存在");
            }
            // 创建缓存路径
            if (!is_dir($this->cacheDir)){
                mkdir($this->cacheDir,755, true);
            }
            $content = file_get_contents($templatePath);
            $compiledContent = $this->parse($content);
            file_put_contents($cachePath, $compiledContent);
        }

        return $cachePath;
    }

    protected function parse($content)
    {
        // 变量输出：{{ variable }}，支持翻译函数
        $content = preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) {
            $expression = $matches[1];
            if (preg_match("/__\('(.+?)'\s*\)/", $expression, $paramMatches)) {
                // 解析 __('key') 形式
                $key = $paramMatches[1];
                return "<?php echo htmlspecialchars(\$this->translate('{$key}')); ?>";
            }
            return "<?php echo htmlspecialchars({$expression}); ?>";
        }, $content);

        // extends 指令：{% extends 'base.html' %}
        $content = preg_replace('/{%\s*extends\s+\'(.+?)\'\s*%}/', '<?php include $this->compile(\'$1\'); ?>', $content);

        // block 指令：{% block content %} ... {% endblock %}
        $content = preg_replace('/{%\s*block\s+(.+?)\s*%}/', '<?php ob_start(); ?>', $content);
        $content = preg_replace('/{%\s*endblock\s*%}/', '<?php echo ob_get_clean(); ?>', $content);

        // if 指令：{% if condition %} ... {% endif %}
        $content = preg_replace('/{%\s*if\s+(.+?)\s*%}/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/{%\s*elif\s+(.+?)\s*%}/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/{%\s*else\s*%}/', '<?php else: ?>', $content);
        $content = preg_replace('/{%\s*endif\s*%}/', '<?php endif; ?>', $content);

        // for 指令：{% for item in items %} ... {% endfor %}
        $content = preg_replace('/{%\s*for\s+(\w+)\s+in\s+(.+?)\s*%}/', '<?php foreach ($2 as $$1): ?>', $content);
        $content = preg_replace('/{%\s*endfor\s*%}/', '<?php endforeach; ?>', $content);

        // 移除多余空白和换行符
        $content = preg_replace('/\s+/', ' ', $content); // 替换多余空格为单个空格
        $content = preg_replace('/>\s+</', '><', $content); // 去掉标签之间的空格和换行符

        return $content;
    }
}
