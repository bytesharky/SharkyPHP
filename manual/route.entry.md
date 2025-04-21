# `Entry` 类使用说明

## 一、概述

 `Entry` 类位于 `Sharky\Core\Router` 命名空间，其主要功能是管理路由条目，包括路由的方法、路径、参数、回调函数以及中间件。

## 二、主要功能及使用方法

### 1.注册中间件（ `middleware` 方法）

- **功能** 此方法用于为当前路由添加中间件。

- **返回值**

    返回当前 `Entry` 实例，支持链式调用。

- **参数说明**

- `$middleware`：需要添加的中间件，既可以是单个中间件，也可以是中间件数组。

- **使用示例**

 ``` php
// 为一个路由注册中间件
Router::reg('GET', '/users', function () {
    // 这里是处理该路由的逻辑，比如查询所有用户并返回结果
    return '查询到的所有用户信息';
})->middleware([
    AuthMiddleware::class
]);
```

### 2.排除中间件（ `withoutMiddleware` 方法）

- **功能** 该方法用于排除当前路由的特定中间件。

- **参数说明**

- `$middleware`：需要排除的中间件，既可以是单个中间件，也可以是中间件数组。

- **返回值**

    返回当前 `Entry` 实例，支持链式调用。

- **使用示例**

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

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2025-3-23 1点

[返回目录](/SharkyPHP.md)