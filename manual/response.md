# `Response` 类使用说明

## 一、概述

`Response` 类位于 `Sharky\Core` 命名空间，用于封装 HTTP 响应的相关信息，包括状态码、响应头、响应体。

## 二、使用说明

在控制器中，可以使用 `$this->response` 获取。

在中间件中，可以使用 `Container` 的 `make` 方法获取。

但是更建议使用全局函数 `response` ,更加便捷。

### 1. `content_type` 方法

```php
public function content_type($contentType)
```

- **功能**：设置响应的内容类型（`Content-Type`）。
- **参数**：
  - `$contentType`：字符串类型，代表要设置的内容类型，像 `text/html`、`application/json` 等。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->content_type('application/json');
```

### 2. `charset` 方法

```php
public function charset($charset)
```

- **功能**：设置响应内容的字符编码。
- **参数**：
  - `$charset`：字符串类型，代表要设置的字符编码，如 `utf-8`、`gbk` 等。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->charset('gbk');
```

### 3. `header` 方法

```php
public function header($key, $value = '')
```

- **功能**：设置响应头信息。
- **参数**：
  - `$key`：可以是字符串类型，代表响应头的键；也可以是数组类型，数组的键值对代表多个响应头信息。
  - `$value`：字符串类型，当 `$key` 为字符串时，此参数代表响应头的值。若 `$value` 是数组，会将数组元素用逗号连接成字符串。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
// 设置单个响应头
$response->header('Cache-Control', 'no-cache');
// 设置多个响应头
$response->header([
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block'
]);
```

### 4. `body` 方法

```php
public function body($body = [])
```

- **功能**：设置响应体内容，同时会根据传入的内容猜测 HTTP 状态码。
- **参数**：
  - `$body`：可以是数组、字符串、布尔值、整数或 `null` 类型。
    - 若为数组、字符串或布尔值 `true`，猜测状态码为 `200`。
    - 若为整数，该整数会作为猜测的状态码。
    - 若为布尔值 `false`，猜测状态码为 `403`。
    - 若为 `null`，猜测状态码为 `200`。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
// 设置响应体为数组
$response->body(['message' => 'Hello, World!']);
// 设置响应体为状态码
$response->body(404);
```

### 5. `json` 方法

```php
public function json($body = [], $status = null, $headers = [])
```

- **功能**：将响应格式设置为 JSON 格式，同时设置响应体内容和 `Content-Type` 响应头。
- **参数**：
  - `$body`：可选参数，默认为空数组。可以是数组、对象等可被 `json_encode` 处理的数据类型，用于作为响应体的内容。
  - `$status`：整数类型，可选参数，代表要设置的 HTTP 状态码。
  - `$headers`：数组类型，可选参数，数组的键值对代表多个响应头信息。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->json(['name' => 'John', 'age' => 30]);
```

### 6. `xml` 方法

```php
public function xml($body = [], $status = null, $headers = [])
```

- **功能**：将响应格式设置为 XML 格式，同时设置响应体内容和 `Content-Type` 响应头。
- **参数**：
  - `$body`：可选参数，默认为空数组。通常是数组类型，用于作为响应体的内容，该数组会被转换为 XML 格式。
  - `$status`：整数类型，可选参数，代表要设置的 HTTP 状态码。
  - `$headers`：数组类型，可选参数，数组的键值对代表多个响应头信息。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->xml(['item' => ['name' => 'Book', 'price' => 20]]);
```

### 7. `html` 方法

```php
public function html($body = '', $status = null, $headers = [])
```

- **功能**：将响应格式设置为 HTML 格式，同时设置响应体内容和 `Content-Type` 响应头。
- **参数**：
  - `$body`：可选参数，默认为空字符串。通常是字符串类型，代表 HTML 代码，用于作为响应体的内容。
  - `$status`：整数类型，可选参数，代表要设置的 HTTP 状态码。
  - `$headers`：数组类型，可选参数，数组的键值对代表多个响应头信息。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->html('<h1>Welcome to my website</h1>');
```

### 8. `status` 方法

```php
public function status($status = 200)
```

- **功能**：设置 HTTP 响应状态码。
- **参数**：
  - `$status`：整数类型，代表要设置的 HTTP 状态码，默认值为 `200`。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->status(404);
```

### 9. `render` 方法

```php
public function render($body, $status = null)
```

- **功能**：设置响应体内容和 HTTP 状态码，状态码若未提供则使用 `body` 方法猜测的状态码。
- **参数**：
  - `$body`：代表要设置的响应体内容。
  - `$status`：整数类型，可选参数，代表要设置的 HTTP 状态码。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->render(['message' => 'Success'], 201);
```

### 10. `redirect` 方法

```php
public function redirect($url, $status = 302)
```

- **功能**：设置重定向的 URL 和 HTTP 状态码。
- **参数**：
  - `$url`：字符串类型，代表要重定向到的 URL。
  - `$status`：整数类型，可选参数，代表重定向的 HTTP 状态码，默认值为 `302`。
- **返回值**：返回当前 `Response` 对象实例，支持链式调用。
- **使用示例**：

```php
$response = new Response();
$response->redirect('https://example.com', 301);
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2025-4-25 5点

[返回目录](/SharkyPHP.md)
