# `Cookie` 类使用说明

## 一、概述

 `Cookie` 类位于 `Sharky\Libs` 命名空间下，是一个用于管理Cookie操作的工具类。它提供了一系列便捷的方法来设置、获取、检查和删除Cookie，使得在PHP应用程序中对Cookie的处理更加简单和规范化，遵循了良好的编程实践。

## 二、类的引入与初始化

在使用 `Cookie` 类之前，需要通过 `use` 语句正确引入包含该。

 ``` php
use Sharky\Libs\Cookie;
 ```

## 三、具体方法使用说明

### 1. `set($key, $value, $expiry = 3600, $path = "/", $domain = "", $secure = false, $httponly = true)` 方法

- **功能**：用于设置一个Cookie。通过调用PHP内置的 `setcookie` 函数来实现Cookie的设置操作，并可指定多个参数来定制Cookie的属性。
- **参数说明**：
  - `$key` ：必选参数，代表Cookie的名称，用于在后续获取、检查或删除该Cookie时进行标识。
  - `$value` ：必选参数，是要存储在Cookie中的值，可以是各种数据类型，不过在存储复杂数据类型时可能需要进行序列化等处理。
  - `$expiry` ：可选参数，默认值为 `3600` 秒（即1小时），用于指定Cookie的过期时间。该值是一个相对于当前时间的秒数偏移量，通过 `time() + $expiry` 来计算具体的过期时间戳。例如，设置为 `7200` 则表示Cookie将在2小时后过期。
  - `$path` ：可选参数，默认值为 `"/"` ，指定了Cookie在服务器端的有效路径。只有当请求的URL路径与设置的 `$path` 匹配时，浏览器才会发送该Cookie。例如，如果设置为 `"/admin"` ，则只有在访问以 `/admin` 开头的URL路径时，浏览器才会带上该Cookie。
  - `$domain` ：可选参数，默认为空字符串，用于指定Cookie的有效域名。如果为空，Cookie将只在设置它的域名下有效。可以设置为具体的域名，如 `".example.com"` ，这样在该域名及其所有子域名下都能访问到该Cookie。
  - `$secure` ：可选参数，默认值为 `false` ，用于指定Cookie是否只能通过安全的连接（如HTTPS）来传输。如果设置为 `true` ，则只有在使用HTTPS协议访问页面时，浏览器才会发送该Cookie。
  - `$httponly` ：可选参数，默认值为 `true` ，设置为 `true` 时，Cookie将只能通过HTTP或HTTPS协议进行访问，而不能通过JavaScript等客户端脚本语言来访问，这有助于提高安全性，防止跨站脚本攻击（XSS）利用Cookie进行恶意操作。
- **示例**：

 ``` php
Cookie::set('username', 'John', 3600, '/', '', false, true);
// 以上代码设置了一个名为'username'，值为'John'的Cookie，1小时后过期，在根路径下有效，
// 适用于当前域名，不要求安全连接且只能通过HTTP/HTTPS协议访问。
 ```

### 2. `get($key, $default = null)` 方法

- **功能**：用于获取指定Cookie的值。通过检查 `$_COOKIE` 超全局变量来获取对应名称的Cookie值，如果该Cookie不存在，则返回指定的默认值。
- **示例**：

 ``` php
$username = Cookie::get('username', 'Guest');
// 尝试获取名为'username'的Cookie值，如果不存在则返回'Guest'。
 ```

### 3. `has($key)` 方法

- **功能**：检查指定的Cookie是否存在。通过检查 `$_COOKIE` 超全局变量中是否存在以指定 `$key` 为名称的元素来判断Cookie是否存在，存在则返回 `true` ，否则返回 `false` 。
- **示例**：

 ``` php
if (Cookie::has('username')) {
    echo "Cookie 'username'存在";
} else {
    echo "Cookie 'username'不存在";
}
 ```

### 4. `delete($key, $path = "/", $domain = "")` 方法

- **功能**：用于删除指定的Cookie。首先通过检查 `$_COOKIE` 超全局变量确认该Cookie存在，然后调用 `setcookie` 函数设置一个同名的Cookie，将其值设为空字符串，并将过期时间设置为当前时间减去3600秒（即让Cookie立即过期），同时指定与原Cookie相同的路径和域名，最后通过 `unset` 函数从 `$_COOKIE` 超全局变量中删除该Cookie的记录。
- **示例**：

 ``` php
Cookie::delete('username');
// 删除名为'username'的Cookie，使用默认的路径'/'和域名（即当前设置的域名）。
 ```

## 四、注意事项

1. 在设置Cookie时，要根据实际需求合理选择Cookie的属性参数，如过期时间、路径、域名、安全连接要求以及是否可通过客户端脚本访问等。不同的设置会影响Cookie的可用性和安全性。
2. 当获取Cookie值时，要注意返回的默认值设置。如果没有合理设置默认值，可能会在Cookie不存在时导致后续代码出现未定义变量等错误。
3. 在删除Cookie时，确保指定的 `$key` 、 `$path` 和 `$domain` 参数与设置该Cookie时使用的参数一致，否则可能无法成功删除Cookie。
4. 由于Cookie存储在客户端浏览器中，其数据大小和存储容量是有限制的，一般每个Cookie的大小不宜超过4KB，所以在存储数据到Cookie时要考虑数据量的大小是否合适。同时，不要在Cookie中存储敏感信息，如密码等，除非采取了足够的加密和安全措施。

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点

[返回目录](/SharkPHP.md)
