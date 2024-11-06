# 模板引擎使用说明

## 一、概述

`Template`类位于 `Sharky\Libs` 命名空间下，是一个模板引擎类，它通过提供了一系列功能用于加载模板、处理模板中的变量、指令等，并进行渲染输出。主要特性包括支持多语言翻译、模板编译缓存以及多种常见的模板指令处理，如变量输出、继承、块定义、条件判断和循环等。

## 二、类的初始化

### 构造函数

```php
public function __construct($lang = 'en')
```

- **参数说明**：
  - `$lang`：可选参数，默认值为`zh`，用于指定加载的语言翻译文件对应的语言代码，如`zh`表示中文等。
- **示例**：

```php
$templateEngine = new Template($lang);
```

### 加载翻译文件

```php
protected function loadTranslations()
```

- 该方法在构造函数中被调用，用于加载指定语言的翻译文件。它会根据构造函数中传入的`$lang`参数，尝试加载形如`lang/{$lang}.php`的文件（例如`lang/zh.php`）。如果文件存在，就将文件中的内容（通常是一个包含翻译键值对的数组）加载到`$this->translations`数组中，以便后续进行翻译操作。

## 三、翻译功能

### 翻译方法

```php
protected function translate($key)
```

- **功能**：根据传入的键`$key`，在已加载的翻译文件对应的`$this->translations`数组中查找对应的翻译文本。如果找到，则返回翻译文本；如果未找到，则返回原键`$key`本身。
- **示例**：
  假设在`zh.php`翻译文件中有`'hello' => '你好'`的翻译键值对，调用`$templateEngine->translate('hello')`将返回`你好`。

## 四、模板渲染

### 渲染方法

```php
public function render($template, $variables = [])
```

- **参数说明**：
  - `$template`：要渲染的模板文件名（包含路径）。
  - `$variables`：可选参数，是一个数组，用于传递模板中需要使用的变量。
- **示例**：

```php
$variables = ['name' => '张三', 'age' => 25];
$output = $templateEngine->render('example.html', $variables);
echo $output;
```

- 该方法首先会调用`compile`方法对模板进行编译，然后通过`extract`函数将传入的变量数组提取到当前作用域，接着使用`ob_start`开启输出缓冲，包含编译后的模板文件，最后通过`ob_get_clean`获取并返回缓冲中的内容，即渲染后的模板输出。

## 五、模板编译

### 编译方法

```php
protected function compile($template)
```

- **功能**：负责对指定的模板文件进行编译处理，并将编译后的内容保存到缓存文件中（如果缓存文件不存在或已过期）。
- **参数说明**：
  - `$template`：要编译的模板文件名（包含路径）。
- **步骤**：
  1. 首先确定模板文件的实际路径`$templatePath`和对应的缓存文件路径`$cachePath`（通过对模板文件名进行`md5`哈希处理并添加`.php`后缀得到）。
  2. 然后判断缓存文件是否存在以及其修改时间是否早于模板文件的修改时间。如果满足这两个条件之一（即缓存文件不存在或已过期），则执行以下操作：
     - 通过`file_get_contents`获取模板文件的原始内容。
     - 调用`parse`方法对原始内容进行解析处理，得到编译后的内容`$compiledContent`。
     - 通过`file_put_contents`将编译后的内容保存到缓存文件中。
  3. 最后返回缓存文件的路径`$cachePath`，以便在渲染方法中包含该文件。

## 六、模板解析

### 解析方法

```php
protected function parse($content)
```

- **功能**：对模板文件的原始内容进行解析处理，将其中的各种模板指令和变量输出格式转换为可执行的PHP代码。

### 变量输出处理

- 对于模板中的变量输出格式`{{ variable }}`，支持翻译函数的情况：
  - 如果变量表达式符合`__('key', {'param': 'value'})`形式（例如`__('greeting', {'name': '张三'})`），则会被解析为`<?php echo htmlspecialchars($this->translate('greeting', {'name': '张三'}));?>`，即先进行翻译操作，然后对结果进行`htmlspecialchars`处理后输出。
  - 如果只是普通变量表达式（如`{{ name }}`），则会被解析为`<?php echo htmlspecialchars(name);?>`，同样进行`htmlspecialchars`处理后输出。

### extends指令处理

- 对于`{% extends 'base.html' %}`指令，会被解析为`<?php include $this->compile('base.html');?>`，即会调用自身的`compile`方法对被继承的模板文件（这里是`base.html`）进行编译并包含进来。

### block指令处理

- 对于`{% block content %}... {% endblock %}`指令：
  - `{% block content %}`会被解析为`<?php ob_start();?>`，开启输出缓冲。
  - `{% endblock %}`会被解析为`<?php echo ob_get_clean();?>`，获取并输出缓冲中的内容。

### if指令处理

- 对于`{% if condition %}... {% endif %}`指令：
  - `{% if condition %}`会被解析为`<?php if ($1):?>`，其中`$1`是条件表达式。
  - `{% elif condition %}`会被解析为`<?php elseif ($1):?>`。
  - `{% else %}`会被解析为`<?php else:?>`。
  - `{% endif %}`会被解析为`<?php endif;?>`。

### for指令处理

- 对于`{% for item in items %}... {% endfor %}`指令：
  - `{% for item in items %}`会被解析为`<?php foreach ($2 as $$1):?>`，其中`$2`是要循环的数组，`$$1`是循环变量。
  - `{% endfor %}`会被解析为`<?php endforeach;?>`。

---

本文档是在AI生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点
