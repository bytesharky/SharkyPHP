# `Exception`类使用说明

## 一、概述

`Exception`类位于`Sharky\Core`命名空间下，它继承自`\Exception`类，主要用于统一处理应用程序中的异常和错误信息。通过设置全局的异常处理函数和错误处理函数，能够根据配置文件中的调试模式设置来决定如何展示错误信息，并且在出现异常或错误时渲染相应的错误页面。

## 二、主要功能及使用方法

### 1. 初始化错误处理（`init`方法）

- **功能**：用于初始化异常和错误处理机制。如果尚未初始化（通过检查`self::$isInit`属性），则会设置全局的异常处理函数为`unityExceptionHandler`方法，设置全局的错误处理函数为`unityErrorHandler`方法，然后调用`recover`方法进行后续的根据配置的处理，最后将`self::$isInit`设置为`true`表示已经完成初始化。
- **使用示例**：

```php
Exception::init();
```

### 2. 根据配置恢复处理（`recover`方法）

- **功能**：根据配置文件中的调试模式设置来决定如何处理错误展示。首先获取`Container`类的实例，通过它来获取`config`实例，然后从配置中获取`isdebug`的值。如果`isdebug`为`true`，则调用`display`方法展示详细错误信息；如果`isdebug`为`false`，则调用`hidden`方法隐藏详细错误信息并展示友好错误页面。
- **使用示例**：

```php
Exception::recover();
```

### 3. 展示详细错误信息（`display`方法）

- **功能**：设置`php.ini`相关配置项，将`display_errors`设置为`1`以显示错误信息，将`error_reporting`设置为`E_ALL`以报告所有类型的错误。
- **使用示例**：

```php
Exception::display();
```

### 4. 隐藏详细错误信息（`hidden`方法）

- **功能**：设置`php.ini`相关配置项，将`display_errors`设置为`0`以隐藏错误信息，将`error_reporting`设置为`0`以不报告任何错误。
- **使用示例**：

```php
Exception::hidden();
```

### 5. 统一处理异常信息（`unityExceptionHandler`方法）

- **功能**：当发生异常时，该方法会被全局的异常处理函数调用。它首先会调用`init`方法确保异常处理机制已初始化，然后获取异常的消息和堆栈跟踪信息，最后调用`renderError`方法来输出错误页面，根据当前的`display_errors`设置决定是展示详细信息还是友好错误页面。
- **使用示例**：
假设在应用程序的其他地方抛出了一个异常，该异常会被此方法处理：

```php
try {
    // 可能抛出异常的代码
    throw new Exception('这是一个测试异常');
} catch (\Exception $e) {
    // 异常会被全局异常处理函数捕获并由unityExceptionHandler处理
}
```

### 6. 统一处理错误信息（`unityErrorHandler`方法）

- **功能**：当发生错误时，该方法会被全局的错误处理函数调用。它首先会调用`init`方法确保异常处理机制已初始化，然后构建包含错误级别、信息、发生文件和行号以及堆栈跟踪信息的详细错误消息，最后调用`renderError`方法来输出错误页面，根据当前的`display_errors`设置决定是展示详细信息还是友好错误页面。
- **使用示例**：
假设在应用程序的代码中出现了一个错误（如调用未定义的函数等），该错误会被此方法处理：

```php
// 假设这里有一个错误，比如调用未定义的函数
some_undefined_function();
```

### 7. 输出错误页面（`renderError`方法）

- **功能**：根据`display_errors`的设置来确定要使用的错误模板文件路径（如果`display_errors`为`false`，使用`SHARKY_ROOT. '/errors/friendly.php'`；如果为`true`，使用`SHARKY_ROOT. '/errors/debug.php'`）。如果模板文件存在，则通过`extract`函数将错误消息和堆栈跟踪信息提取到变量中，然后使用`include`函数包含模板文件来渲染错误页面；如果模板文件不存在，则输出提示信息“模板文件不存在!”，最后通过`die()`终止程序。
- **使用示例**：
该方法通常在`unityExceptionHandler`和`unityErrorHandler`方法内部被调用，不需要在应用程序的其他地方直接调用，但为了说明其功能示例如下：

```php
$message = '这是一个错误消息';
$traceStr = '这是堆栈跟踪信息';
Exception::renderError($message, $traceStr);
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点
