<?php

/**
 * @description 微模版引擎
 * @author Sharky
 * @date 2025-3-22
 * @version 1.1.1
 *
 */

namespace Sharky\Libs;

use Sharky\Core\Container;
use Sharky\Utils\ArrayUtils;

class Template
{
    protected $templateDir;
    protected $cacheDir;
    protected $translations = [];
    protected $blocks = [];
    protected $isDebug = false;

    public function __construct($lang = 'zh')
    {
        // 加载配置文件
        $container = Container::getInstance();
        $config = $container->make('config');
        $this->isDebug = $config->get('config.isdebug', false);

        // 视图路径和缓存路径
        $templateDir = SITE_ROOT . DIRECTORY_SEPARATOR . $config->get('config.template.path', 'views');
        $cacheDir = SITE_ROOT . DIRECTORY_SEPARATOR . $config->get('config.template.cache', 'caches');

        $this->templateDir = rtrim($templateDir, DIRECTORY_SEPARATOR);
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);

        // 设置多语言
        $this->setLanguage($lang);
    }

    public function setLanguage($lang){
        if (!$lang){
            return;
        }
        // 加载配置文件
        $container = Container::getInstance();
        $config = $container->make('config');
        // 加载多语言
        $langPath = $config->get('config.language.path');
        $defaultLang = $config->get('config.language.default');
        $defaultFile = implode(DIRECTORY_SEPARATOR, [SITE_ROOT, $langPath, $defaultLang.".php"]);
        $userFile = implode(DIRECTORY_SEPARATOR, [SITE_ROOT, $langPath, $lang.".php"]);
        // 默认语言
        $defaultTranslations = [];
        if ($defaultFile && file_exists($defaultFile)) {
            $defaultTranslations = include $defaultFile;
        }
        // 用户选择语言
        $userTranslations = [];
        if ($userFile && file_exists($userFile)) {
            $userTranslations = include $userFile;
        }

        $this->translations = ArrayUtils::deepMerge($defaultTranslations, $userTranslations);
    }

    public function translate($key)
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
        $content = ob_get_clean();
        return $content;
    }

    protected function compile($template, $isParse = true)
    {
        $template = preg_replace('/[\\\\\/]/', DIRECTORY_SEPARATOR, $template);
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR . $template;
        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR;
        $cacheFile = $cachePath . md5($template) . '.php';
        
        if ($this->isDebug){
            $timestamp = time();
            $sixDigitNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $combinedString = $timestamp . $sixDigitNumber;

            $cacheFile = $cachePath . str_replace(DIRECTORY_SEPARATOR, "-", $template) . '-' . md5($combinedString) . '.php';
        }

        if ($this->isDebug || 
            !file_exists($cacheFile) || 
            filemtime($cacheFile) < filemtime($templatePath)) {
            if (!file_exists($templatePath)) {
                throw new \Exception("模版文件{$templatePath}不存在");
            }
            // 创建缓存路径
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }
            $content = file_get_contents($templatePath);
            // 处理模版继承
            $content = $this->parseExtends($content);
            // 解析模板
            if ($isParse){
                $content = $this->parse($content);
            }
            file_put_contents($cacheFile, $content);
        }

        return $cacheFile;
    }

    protected function parse($content)
    {

        // 渲染Block
        $content = $this->renderBlocks($content);

        // 引入其他模板
        if (preg_match('/{%\s*include\s*[\'"](.+?)[\'"]\s*%}/', $content, $matches)) {
            $includeTemplate = $matches[1];
            $that = new self();
            $compiledTemplate = $that->compile($includeTemplate);
            $templateContent = file_get_contents($compiledTemplate);
            $content = str_replace($matches[0], $templateContent, $content);
        }

        // 变量/常量输出，支持翻译函数
        $content = preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) {
            $expression = $matches[1];

            if (preg_match('/__\(\s*[\'"](.+?)[\'"]\s*\)/', $expression, $paramMatches)) {
                // 解析 __('key') 形式
                $key = $paramMatches[1];
                return "<?php echo \$this->translate('{$key}'); ?>";
            } else if (preg_match('/__\(\s*(.+?)\s*\)/', $expression, $paramMatches)) {
                // 解析 __(key) 形式
                $key = $paramMatches[1];
                $key = $this->getExpression($key);
                return "<?php echo \$this->translate({$key}); ?>";
            } else {
                $expression = $this->getExpression($expression);
                return "<?php echo {$expression}; ?>";
            }
        }, $content);

        // 简化控制流指令正则表达式
        $patterns = [
            '/{%\s*if\s+(.+?)\s*%}/' => '<?php if ($1): ?>',
            '/{%\s*elif\s+(.+?)\s*%}/' => '<?php elseif ($1): ?>',
            '/{%\s*else\s*%}/' => '<?php else: ?>',
            '/{%\s*endif\s*%}/' => '<?php endif; ?>',
            '/{%\s*for\s+(.+)\s+in\s+(.+?)\s*%}/' => '<?php foreach ($2 as $1): ?>',
            '/{%\s*endfor\s*%}/' => '<?php endforeach; ?>'
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        $content = $this->compressHtml($content);

        return (!$content || !is_string($content)) ? "" : $content;
    }

    function compressHtml($content)
    {
        // 移除模板注释
        $content = preg_replace('/{#\s*(.+?)\s*#}/s', ' ', $content);

        // 移除HTML中的普通注释
        $content = preg_replace('/<!--(?!\[if\s).*?-->/s', '', $content);
        
        // 移除JavaScript块和style块中的单行注释和多行注释
        $content = preg_replace_callback('/<(script|style).*?>(.+?)<\/(script|style)>/s', function ($match) {
            $jscript = $match[0] . "\n";
            $jscript = preg_replace('/\/\/.*?\n/', '', $jscript);
            return preg_replace('/\/\*[\s\S]*?\*\//', '', $jscript);
        }, $content);

        // 移除多余空白和换行符
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/>\s+</', '><', $content);

        return $content;
    }

    protected function getExpression($expression)
    {
        return preg_replace_callback('/^\s*(.+?)(\?\?)?\s*$/', function ($paramMatches) {
            $key = $paramMatches[1];
            $key = defined($key) ? $key : "\$$key";
            $key = (isset($paramMatches[2]) && $paramMatches[2] === "??") ? $key . "??" . "\"\"" : $key;
            return $key;
        }, $expression);
    }

    protected function parseExtends($content)
    {
        $extendsPattern = '/\s?{%\s*extends\s*[\'"](.+?)[\'"]\s*%}/s';
        if (!preg_match_all($extendsPattern, $content, $allMatches)) {
            return $content;
        }

        if (count($allMatches[0]) > 1) {
            throw new \Exception('不允许存在多个extends语句');
        }

        if (!preg_match('/^' . substr($extendsPattern, 1), $content, $matches)) {
            throw new \Exception('extends语句前不能有其他语句');
        } else {
            $parentCompiled = $this->compile($matches[1], false);
            $parentContent = file_get_contents($parentCompiled);
            // 处理 block 指令 {% block content %}... {% endblock %}
            $this->parseBlocks($parentContent);
            $content = $this->parseBlocks($content);
            $content = str_replace($matches[0], $parentContent, $content);
            $content = $this->parseRenderBlocks($content);
        }
        
        return $content;
    }

    protected function parseBlocks($content)
    {
        $blockPattern = '/{%\s*block\s*(.+?)\s*%}(.*?){%\s*endblock\s*%}/s';
        if (preg_match_all($blockPattern, $content, $blockMatches, PREG_SET_ORDER)) {
            $blocks = [];
            foreach ($blockMatches as $blockMatch) {
                $blockName = $blockMatch[1];
                $blockContent = $blockMatch[2];
                if (isset($blocks[$blockName])) {
                    throw new \Exception("block名称[{$blockName}]重定义");
                } else {
                    $blocks[$blockName] = $blockContent;
                }
            }
            $this->blocks = array_merge($this->blocks, $blocks);
        }


        return preg_replace_callback($blockPattern, function ($matches) {
            $blockName = $matches[1];
            if (isset($this->blocks[$blockName])) {
                return "";
            }
            return $matches[0];
        }, $content);
    }

    protected function renderBlocks($content)
    {
        $blockPattern = '/{%\s*block\s*(.+?)\s*%}(.*?){%\s*endblock\s*%}/s';
        $content = preg_replace_callback($blockPattern, function ($matches) {
            $blockName = $matches[1];
            if (isset($this->blocks[$blockName])) {
                return $this->blocks[$blockName];
            }
            return $matches[0];
        }, $content);
        return $content;
    }

    protected function parseRenderBlocks($content)
    {
        $blockPattern = '/{%\s*block\s*(.+?)\s*%}(.*?){%\s*endblock\s*%}/s';
        $content = preg_replace_callback($blockPattern, function ($matches) {
            $blockName = $matches[1];
            if (isset($this->blocks[$blockName])) {
                return '{% block '. $blockName .' %}'.$this->blocks[$blockName].'{% endblock %}';
            }
            return $matches[0];
        }, $content);
        return $content;
    }
}
