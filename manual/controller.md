# `Controller` 类使用说明

## 一、概述

 `Controller` 类位于 `Sharky\Core` 命名空间下，是框架中的控制器模块，主要负责处理与业务逻辑相关的操作，如获取配置信息以及在出现错误时渲染相应的错误页面等。

## 二、类的初始化

 `Controller` 类在 `Route` 请求到达时，框架自动完成创建和初始化操作。

## 三、主要方法说明

### 1. `renderRouter($response)` 方法

- **功能**：此方法主要用于根据不同类型的响应数据来进行相应处理，进而渲染合适的输出内容。它可以根据响应数据的类型、状态码以及配置信息等，决定是直接返回数据、进行 JSON 编码返回、设置重定向头，还是渲染对应的错误页面等操作，以适配不同的应用场景需求。

通常你不要手动调用它，框架会自动调用此函数。

- **参数说明**：
  - `$response` ：传入的响应数据，可以是多种类型，如数组、字符串、整数、布尔值、空值（`null`）等，不同类型的数据会在方法内部按照相应逻辑进行处理。

- **操作流程**：
  1. **根据 `$response` 类型来定确定 `$json` 的值**：
     - 首先，通过获取配置项 `config.restful` 的值赋给 `$restful` 变量。
     - 接着，判断 `$response` 的类型：
       - 若 `$response` 是数组或者字符串类型，直接将 `$response` 赋值给 `$json`。
       - 若 `$response` 是整数类型，则调用 `fetchStatusJson($response)` 方法，并将返回结果赋值给 `$json`。
       - 若 `$response` 为 `null`，则将空字符串赋值给 `$json`。
       - 若 `$response` 是布尔值且为 `true`，调用 `fetchStatusJson(200)` 方法，将返回值赋给 `$json`。
       - 若不满足上述类型情况，则将一个包含默认错误信息的数组（如 `['code'=> 500, 'status' => 'Server Error','message'=> 'Internal Server Error']` ）赋值给 `$json`。
  2. **确定 HTTP 响应状态码 `$code`**：
     - 从 `$json` 数组中尝试获取 `code` 键对应的值，如果不存在则默认为 `200`，并通过 `http_response_code($code)` 设置 HTTP 响应状态码为该值。
  3. **根据状态码和数据类型进行不同处理**：
     - **状态码为 `200` 时**：
       - 若 `$json` 是数组类型，则使用 `json_encode($json)` 对其进行 JSON 编码并返回；若 `$json` 不是数组类型，则直接返回 `$json`。
     - **状态码不为 `200` 时**：
       - 若状态码属于 `[301, 302, 303, 307, 308]` 这些重定向相关的状态码，并且 `$json` 数组中存在 `Location` 键，则通过 `header('Location: '. $json['Location'])` 设置重定向头。
       - 接着判断配置项 `$restful` 的值，如果其值转换为小写后等于 `"json"`，则使用 `json_encode($json)` 对 `$json` 进行 JSON 编码并返回；若 `$restful` 不满足该条件，则：
         - 先拼接出错误模板所在目录路径（通过 `SHARKY_ROOT. '/errors/'` ）并赋值给 `$errorTemplatePath`，再根据状态码拼接出具体的错误文件路径（如 `"{$code}.php"` ）赋值给 `$errorFile`。
         - 若该错误文件存在（通过 `file_exists($errorFile)` 判断），则开启输出缓冲（`ob_start()`），提取 `$json` 和 `$_SERVER['REQUEST_METHOD']` 变量到当前作用域（`extract(['error' => $json, 'method' => $_SERVER['REQUEST_METHOD']])`），包含该错误文件（`include $errorFile`），最后通过 `ob_get_clean()` 获取并返回缓冲内容；若文件不存在，则尝试从 `$json` 数组中获取 `message` 键对应的值，若不存在则默认为 `'Unknown Error'`，并返回包含状态码和错误消息的字符串（如 `"{$code} Error - {$message}"` ）。

## 四、扩展函数

为了更方便的去除模板引擎，下面两个函数我写在了 `APP\Controller` 里

### 1. `render($name, array $context = [])`* 函数

- **功能**：
该函数用于渲染模板并返回渲染后的内容。它提供了两种渲染方式，一种是在类 `Sharky\Libs\Template` 存在的情况下，利用这个类的实例来进行模板渲染；另一种是在该类不存在时，通过传统的包含文件以及提取变量到当前作用域的方式来实现模板渲染，若模板文件不存在则会终止程序并输出相应提示信息。

- **参数说明**：
  - `$name` ：表示要渲染的模板名称，通常是一个文件名（包含路径相关信息，具体取决于配置或默认的模板目录结构），用于定位对应的模板文件。
  - `array $context = []` ：这是一个可选参数，默认值为空数组，用于传递在模板中可能会用到的变量数据，以关联数组的形式提供，数组的键为变量名，值为对应的变量值，这些变量会在模板渲染时被提取到相应的作用域中供模板使用。

- **操作流程**：
  1. **基于模板类的渲染方式（若类存在）**：
     - 首先，通过 `class_exists("Sharky\\Libs\\Template")` 判断类 `Sharky\Libs\Template` 是否存在。若存在，则创建该类的一个实例 `$template`（`$template = new \Sharky\Libs\Template();`），然后调用实例的 `render($name, $context)` 方法，并将该方法的返回结果直接返回，以此完成模板的渲染并返回渲染后的内容。
  2. **传统的模板渲染方式（若模板类不存在）**：
     - 先通过 `$this->config->get("config.template.path")` 获取配置中定义的模板目录路径，并赋值给 `$templateDir`。然后将模板名称与模板目录路径进行拼接，得到完整的模板文件路径 `$templatePath`（`$templatePath = $templateDir. DIRECTORY_SEPARATOR. $name;`）。
     - 接着，使用 `file_exists($templatePath)` 判断该模板文件是否存在。若不存在，则通过 `die("模板文件不存在: $name");` 终止程序并输出相应提示信息，告知用户模板文件不存在。
     - 若模板文件存在，则开启输出缓冲（`ob_start();`），将 `$context` 数组中的变量提取到当前作用域（`extract($context);`），再通过 `include $templatePath` 包含该模板文件，使模板中的代码得以执行，最后使用 `ob_get_clean()` 获取并返回输出缓冲中的内容，也就是渲染后的模板内容。

### 2. `display($name, array $context = [])`* 函数

- **功能**：
此函数用于直接输出渲染后的模板内容到页面上，其内部实际是调用了 `render($name, $context)` 函数获取渲染后的内容，然后通过 `echo` 将内容输出，若不需要使用模板引擎，可将此函数删除。

- **参数说明**：
参数与 `render($name, array $context = [])` 函数一致，`$name` 用于指定模板名称，`array $context = []` 用于传递模板中要使用的变量数据。

- **操作流程**：
直接调用 `$this->render($name, $context)` 获取渲染后的模板内容，并通过 `echo` 将其输出到页面上，该函数没有返回值（返回类型为 `void`），它的主要作用就是将渲染结果展示出来。

如果使用了模板引擎，则可以使用 `render` 获取渲染后的代码，然后使用 `return` 输出，或者也可以直接使用 `display` 输出，但是注意 `display` 并不会终止程序。

## 五、使用步骤

### 1. 创建 `Controller` 类的实例

框架会自动根据路由自动注入依赖并创建控制器实例，然后调用指定方法。

### 2. 在控制器，你可以 `return` 一个[ `int`, `string`, `bool`, `array` ]类型的参数，框架会自动调用 `renderRouter` 渲染输出

例如需要返回一个404页面：

 ``` php
$errorInfo = [
    'code' => 404,
    'message' => 'Page Not Found'
];
$controller->renderRouter($errorInfo);
 ```

或者更简单一点可以直接

``` php
$controller->renderRouter(404);
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-12-7 7点

[返回目录](/SharkyPHP.md)
