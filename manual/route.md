# `Router` 类使用说明

## 一、概述

 `Router` 类位于 `Sharky\Core` 命名空间下，是框架中的路由管理模块，主要负责加载路由文件、注册路由及路由分组、格式化路径以及根据请求的方法和URI来派遣路由，从而确定要执行的相应控制器方法或回调函数。

## 二、主要功能及使用方法

### 1. 加载路由（ `loadRoutes` 方法）

- **功能**：用于加载项目中的路由文件。它会根据指定的路径（通过拼接 `SITE_ROOT` 、 `'routes'` 和空字符串形成完整路径），使用 `glob` 函数获取该路径下所有的 `.php` 文件，然后通过 `require_once` 逐个包含这些路由文件，使得其中定义的路由能够被注册到路由表中。
- **使用示例**：
框架启动时会自动扫描并加载路由，通常无需手动加载。

### 2. 注册路由（ `reg` 方法）

- **功能**：用于注册单个路由。接受请求方法（ `$method` ）、路径（ `$path` ）和回调函数或控制器方法（ `$callback` ）作为参数。在注册过程中，会先应用路由分组设置的 `prefix` 和 `controller` 选项（如果存在）对路径和回调函数进行处理，然后将处理后的路由信息（包括请求方法、格式化后的路径以及回调函数）添加到路由表（ `self::$routes` ）中。
- **参数说明**：
  - `$method` ：表示请求的方法，如 `GET` 、 `POST` 等，也可以是 `ALL` 表示匹配所有请求方法。
  - `$path` ：路由的路径，可以包含参数占位符，如 `{id}` ，用于动态匹配不同的路径值。
  - `$callback` ：可以是一个简单的回调函数，或者是一个数组形式的控制器方法，格式为`[控制器类名, 方法名]`。
- **使用示例**：
所有路由配置都应该放在 `app/routes` 问目录下，框架会自动扫描并加载它们。
如果开启多站点则是 `app/subsite/routes` 目录下，框架同样会自动扫描并加载它们。

 ``` php
// 注册一个GET请求的路由
Router::reg('GET', '/users', function () {
    // 这里是处理该路由的逻辑，比如查询所有用户并返回结果
    return '查询到的所有用户信息';
});

// 注册一个POST请求到指定控制器方法的路由
Router::reg('POST', '/users/create', ['UserController', 'create']);
 ```

### 3. 注册路由（ `preg_reg` 方法）

  与 `reg` 方法类似，利用正则表达式，实现更高级的自定义路由。

  `$path` 应传入一个正则表达式；

  `$params` 应传入一个参数名数组，按照正则表达式比配组的顺序。

### 4. 注册路由分组（ `group` 方法）

- **功能**：用于注册一组具有相同前缀或其他共享配置的路由。接受一个包含分组选项的数组（ `$options` ）和一个回调函数（ `$callback` ）作为参数。在分组注册过程中，会先保存当前的分组选项，然后将传入的分组选项与当前选项合并，接着执行回调函数来注册分组内的路由，最后恢复之前的分组选项。分组选项常见的有 `prefix` （路由路径前缀）和 `controller` （默认控制器类名）等。
- **参数说明**：
  - `$options` ：一个数组，包含分组的各种配置选项，如 `['prefix' => '/admin', 'controller' => 'AdminController']` ，表示该分组下的路由路径都将添加 `/admin` 前缀，并且如果回调函数是字符串形式，将默认使用 `AdminController` 作为控制器类名。
  - `$callback` ：一个回调函数，在该函数内部可以通过调用 `reg` 方法来注册分组内的各个路由。
- **使用示例**：

 ``` php
Router::group(['prefix' => '/admin', 'controller' => 'AdminController'], function () {
    // 注册分组内的路由
    Router::reg('GET', '/dashboard', ['AdminController', 'dashboard']);
    Router::reg('POST', '/users/add', ['AdminController', 'addUser']);
});
 ```

### 5. 格式化路径（ `formatPath` 方法）

- **功能**：将传入的路由路径（ `$path` ）格式化为正则表达式形式，以便后续能够通过正则匹配来确定请求的URI是否匹配该路由。它会将路径中的参数占位符（如 `{id}` ）转换为对应的正则表达式捕获组，将普通路径部分进行转义处理，最终生成一个完整的正则表达式字符串。
- **使用示例**：
假设已经有一个路由路径为 `/users/{id}` ，在注册路由时会自动调用该方法进行格式化，无需在应用程序其他地方直接调用该方法。

### 6. 派遣路由（ `dispatch` 方法）

- **功能**：根据当前请求的方法（ `$method` ）和URI（ `$uri` ）来查找并执行匹配的路由。首先会对URI进行预处理，去除参数部分（只保留 `?` 之前的部分）以及结尾的斜杠，然后遍历路由表中的所有路由，通过正则匹配来判断请求的URI是否匹配某条路由。如果找到匹配的路由且请求方法也符合要求，则取出匹配到的参数并调用相应的控制器方法或回调函数；如果没有找到匹配的路由，或者找到的路由但请求方法不匹配，则会调用 `Controller` 类的 `renderErrorPage` 方法来返回相应的错误页面（如404或405错误）。
- **使用示例**：
通常在应用程序启动后，当接收到请求时会调用该方法来处理路由派遣。例如在框架的核心 `App` 类的 `run` 方法中可能会有如下调用：

 ``` php
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$router->dispatch($method, $uri);
 ```

### 7. 调用控制器方法（ `callControllerMethod` 方法）

- **功能**：用于调用控制器类的方法。如果传入的回调函数是数组形式且第一个元素是字符串（表示控制器类名），则会先实例化该控制器类，然后调用指定的方法，并传递相应的参数；如果是简单的回调函数，则直接通过 `call_user_func_array` 调用该回调函数并传递参数。
- **使用示例**：
该方法通常在 `dispatch` 方法内部被调用，用于执行匹配到的路由对应的控制器方法或回调函数。例如在 `dispatch` 方法中找到匹配路由后会有如下调用：

 ``` php
$callback = $route['callback'];
$params = $matchedParams;
$result = $this->callControllerMethod($callback, $params);
 ```

### 8. 中间件注册函数（ `middleware` 方法）

- **功能**：用于注册路由中间件。
- **参数说明**：
  - `$middleware` ：路由中间件数组。
- **使用示例**：

 ``` php
// 为一个路由注册中间件
Router::reg('GET', '/users', function () {
    // 这里是处理该路由的逻辑，比如查询所有用户并返回结果
    return '查询到的所有用户信息';
})->middleware([
    AuthMiddleware::class
]);
```

``` php
// 为一组路由注册中间件
Router::middleware([
    AuthMiddleware::class
])->group(['prefix' => '/demo'], function () {
    Router::reg('GET', '/list', [DemoController::class, 'list']);
    Router::reg('DELETE', '/{id}', [DemoController::class, 'delete']);
    Router::reg('GET', '/{id}', [DemoController::class, 'show']);
});
```

### 9. 中间件排除函数（ `withoutMiddleware` 方法）

- **功能**：用于排除路由中间件。
- **参数说明**：
  - `$middleware` ：路由中间件数组。
- **使用示例**：

```php
// 为路由排除中间件
Router::middleware([
    AuthMiddleware::class
])->group(['prefix' => '/demo'], function () {
    Router::reg('GET', '/list', [DemoController::class, 'list'])->withoutMiddleware([
        AuthMiddleware::class
    ]);
    Router::reg('DELETE', '/{id}', [DemoController::class, 'delete']);
    Router::reg('GET', '/{id}', [DemoController::class, 'show']);
});
```

### 10. 便捷方法

本次更新新增以下便捷方法

#### 便捷方法 - GET

```php
public static function get($path, $callback, $matchMode = -1)
{
    return self::reg('GET', $path, $callback, $matchMode);
}
```

#### 便捷方法 - POST

```php
public static function post($path, $callback, $matchMode = -1)
{
    return self::reg('POST', $path, $callback, $matchMode);
}
```

#### 便捷方法 - PUT

```php
public static function put($path, $callback, $matchMode = -1)
{
    return self::reg('PUT', $path, $callback, $matchMode);
}php
```

#### 便捷方法 - DELETE

```php
public static function delete($path, $callback, $matchMode = -1)
{
    return self::reg('DELETE', $path, $callback, $matchMode);
}
```

#### 便捷方法 - ALL HTTP方法

```php
public static function any($path, $callback, $matchMode = -1)
{
    return self::reg('ALL', $path, $callback, $matchMode);
}
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2025-3-22 17点

[返回目录](/SharkyPHP.md)
