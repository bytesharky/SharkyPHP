# `Request` 类使用说明

## 一、概述

`Request` 类位于 `Sharky\Core` 命名空间，用于封装 HTTP 请求的相关信息，包括请求参数、URI 和请求方法，同时还能处理自定义属性。

## 二、使用说明

在控制器中，可以使用 `$this->request` 获取。

在中间件中，可以通过入口函数 `handle` 的 `$request` 参数获取。

### 只读属性

- `$params`：存储请求的参数，数据类型为 `array`。
- `$uri`：存储请求的 URI，数据类型为 `string`。
- `$method`：存储请求的方法（如 GET、POST 等），数据类型为 `string`。

### 魔术方法

#### `__get` 方法

```php
public function __get($name)
```

- **功能** 此方法用于获取自定义属性的值。

- **返回值**

如果属性存在，则返回其值；若不存在，则返回 `null`。

- **使用示例**

```php
$request->newAttribute = 'value';
echo $request->newAttribute; // 输出: value
```

#### `__set` 方法

```php
public function __set($key, $value)
```

- **功能** 该方法用于设置自定义属性的值。

- **使用示例**

```php
$request->newAttribute = 'new value';
```
