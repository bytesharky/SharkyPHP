# `Session` 类使用说明

## 1. 概述

 `Session` 类位于 `Sharky\Libs` 命名空间下，是一个用于管理Session操作的工具类，它提供了一系列便捷的方法来处理会话相关的操作，如启动会话、设置会话变量、获取会话变量值、检查变量是否存在、删除特定变量以及销毁整个会话等。

## 2. 类的引入与初始化

在使用该 `Session` 类之前，需要通过 `use` 语句正确引入包含该。

 ``` php
use Sharky\Libs\Session;
 ```

## 3. 具体方法使用说明

### 3.1 start() 方法

- **功能**：用于启动PHP会话。如果当前会话状态为尚未启动（ `session_status() === PHP_SESSION_NONE` ），则调用 `session_start()` 来开启会话。
- **使用示例**：

 ``` php
Session::start();
 ```

### 3.2 set($key, $value) 方法

- **功能**：设置一个会话变量。接受两个参数， `$key` 为要设置的会话变量的键名， `$value` 为对应的值。将会把指定的 `$value` 赋值给 `$_SESSION` 数组中以 `$key` 为索引的元素。
- **使用示例**：

 ``` php
Session::start(); // 先启动会话
Session::set('username', 'John'); // 设置一个名为'username'，值为'John'的会话变量
 ```

### 3.3 get($key, $default = null) 方法

- **功能**：获取指定会话变量的值。接受一个必需参数 `$key` ，即要获取值的会话变量的键名。还可接受一个可选参数 `$default` ，如果指定的会话变量不存在，则返回该默认值。如果会话变量存在，则返回其对应的值。
- **使用示例**：

 ``` php
Session::start();
$username = Session::get('username', 'Guest'); // 获取'username'会话变量的值，如果不存在则返回'Guest'
 ```

### 3.4 has($key) 方法

- **功能**：检查指定的会话变量是否存在。接受一个参数 `$key` ，即要检查的会话变量的键名。如果 `$_SESSION` 数组中存在以该 `key` 为索引的元素，则返回 `true` ；否则返回 `false` 。
- **使用示例**：

 ``` php
Session::start();
if (Session::has('username')) {
    echo "会话变量'username'存在";
} else {
    echo "会话变量'username'不存在";
}
 ```

### 3.5 delete($key) 方法

- **功能**：删除指定的会话变量。接受一个参数 `key` ，即要删除的会话变量的键名。如果 `$_SESSION` 数组中存在以该 `key` 为索引的元素，则通过 `unset()` 函数将其删除。
- **使用示例**：

 ``` php
Session::start();
Session::delete('username'); // 删除名为'username'的会话变量
 ```

### 3.6 destroy() 方法

- **功能**：销毁整个会话。如果当前会话状态不是尚未启动（ `session_status()!== PHP_SESSION_NONE` ），则先通过 `session_unset()` 清空会话变量，再通过 `session_destroy()` 销毁会话。
- **使用示例**：

 ``` php
Session::start();
//... 其他会话相关操作
Session::destroy(); // 完成所有操作后，销毁会话
 ```

## 4. 注意事项

- 在使用该 `Session` 类的任何方法之前，通常需要先调用 `start()` 方法来启动会话，除了 `start()` 方法本身外，其他方法在会话未启动的情况下可能无法正常工作或会导致错误。
- 当设置、获取、检查或删除会话变量时，确保传递的键名准确无误，否则可能无法正确操作对应的会话变量。
- 销毁会话时要谨慎操作，因为一旦会话被销毁，所有与之相关的会话变量信息都将丢失，并且如果后续还需要使用会话功能，需要重新启动会话。

---

本文档是在AI生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点

[返回目录](/SharkyPHP.md)
