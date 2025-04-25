# `Cache` 类使用说明

`Cache` 类位于 `Sharky\Core` 命名空间下，是一个用于管理缓存的工具类，它支持文件缓存和 Redis 缓存两种方式，并且可以根据配置灵活切换。该类提供了一系列静态方法，用于初始化缓存设置、存储和获取缓存数据、删除缓存以及检查缓存是否存在等操作。同时，它还支持会话级别的缓存和共享缓存，方便在不同场景下使用。

## 一、概述



### 初始化相关方法

#### `init()`

- **功能**：初始化缓存路径和默认设置。如果缓存路径不存在，会创建该路径。同时，会从配置中读取缓存相关的设置，如路径、过期时间和缓存类型。
- **用法示例**：

```php
Cache::init();
```

#### `setCachePath($path)`

- **功能**：设置文件缓存路径。如果路径不存在，会创建该路径。
- **参数**：
  - `$path`：缓存路径，字符串类型。
- **用法示例**：

```php
Cache::setCachePath('/new/cache/path');
```

#### `getCachePath()`

- **功能**：获取当前设置的文件缓存路径。
- **返回值**：缓存路径，字符串类型。
- **用法示例**：

```php
$cachePath = Cache::getCachePath();
echo $cachePath;
```

#### `useFile()`

- **功能**：设置使用文件缓存。
- **用法示例**：

```php
Cache::useFile();
```

#### `useRedis()`

- **功能**：设置使用 Redis 缓存。
- **用法示例**：

```php
Cache::useRedis();
```

#### `setDefaultExpire($seconds)`

- **功能**：设置默认的缓存过期时间。
- **参数**：
  - `$seconds`：过期时间，整数类型，单位为秒。
- **用法示例**：

```php
Cache::setDefaultExpire(7200);
```

### 缓存操作方法

#### `set($key, $value, $expire = null)`

- **功能**：存储数据到缓存，默认使用当前会话 ID。
- **参数**：
  - `$key`：缓存键，字符串类型。
  - `$value`：缓存值，可以是任意类型。
  - `$expire`：过期时间，整数类型，单位为秒，默认为`null`，表示使用默认过期时间。
- **返回值**：存储是否成功，布尔类型。
- **用法示例**：

```php
$success = Cache::set('my_key', 'my_value', 1800);
if ($success) {
    echo "缓存设置成功";
} else {
    echo "缓存设置失败";
}
```

#### `setShared($key, $value, $expire = null)`

- **功能**：存储共享数据到缓存，使用`shared`作为会话标识。
- **参数**：
  - `$key`：缓存键，字符串类型。
  - `$value`：缓存值，可以是任意类型。
  - `$expire`：过期时间，整数类型，单位为秒，默认为`null`，表示使用默认过期时间。
- **返回值**：存储是否成功，布尔类型。
- **用法示例**：

```php
$success = Cache::setShared('shared_key', 'shared_value', 3600);
if ($success) {
    echo "共享缓存设置成功";
} else {
    echo "共享缓存设置失败";
}
```

#### `get($key, $default = null)`

- **功能**：从缓存中获取数据，优先使用当前会话 ID。
- **参数**：
  - `$key`：缓存键，字符串类型。
  - `$default`：默认值，当缓存未找到时返回，默认为`null`。
- **返回值**：缓存值或默认值。
- **用法示例**：

```php
$value = Cache::get('my_key', 'default_value');
echo $value;
```

#### `getShared($key, $default = null)`

- **功能**：从共享缓存中获取数据。
- **参数**：
  - `$key`：缓存键，字符串类型。
  - `$default`：默认值，当缓存未找到时返回，默认为`null`。
- **返回值**：缓存值或默认值。
- **用法示例**：

```php
$sharedValue = Cache::getShared('shared_key', 'default_shared_value');
echo $sharedValue;
```

#### `delete($key)`

- **功能**：删除指定缓存键的缓存，使用当前会话 ID。
- **参数**：
  - `$key`：缓存键，字符串类型。
- **返回值**：删除是否成功，布尔类型。
- **用法示例**：

```php
$deleted = Cache::delete('my_key');
if ($deleted) {
    echo "缓存删除成功";
} else {
    echo "缓存删除失败";
}
```

#### `deleteShared($key)`

- **功能**：删除指定共享缓存键的缓存。
- **参数**：
  - `$key`：缓存键，字符串类型。
- **返回值**：删除是否成功，布尔类型。
- **用法示例**：

```php
$deleted = Cache::deleteShared('shared_key');
if ($deleted) {
    echo "共享缓存删除成功";
} else {
    echo "共享缓存删除失败";
}
```

#### `exists($key)`

- **功能**：检查指定缓存键的缓存是否存在，使用当前会话 ID。
- **参数**：
  - `$key`：缓存键，字符串类型。
- **返回值**：缓存是否存在，布尔类型。
- **用法示例**：

```php
$exists = Cache::exists('my_key');
if ($exists) {
    echo "缓存存在";
} else {
    echo "缓存不存在";
}
```

#### `existsShared($key)`

- **功能**：检查指定共享缓存键的缓存是否存在。
- **参数**：
  - `$key`：缓存键，字符串类型。
- **返回值**：缓存是否存在，布尔类型。
- **用法示例**：

```php
$exists = Cache::existsShared('shared_key');
if ($exists) {
    echo "共享缓存存在";
} else {
    echo "共享缓存不存在";
}
```

### 会话 ID 相关方法

#### `getSessionId()`

- **功能**：获取当前会话 ID。如果会话未启动，会启动会话。
- **返回值**：会话 ID，字符串类型。
- **用法示例**：

```php
$sessionId = Cache::getSessionId();
echo $sessionId;
```

#### `setSessionId($sessionId)`

- **功能**：设置会话 ID，如果是你无法使用session可以可使用url参数，请求头等方式获取 `sessionId`，然后使用此方法手动指定一个会话。
- **参数**：
  - `$sessionId`：会话 ID，字符串类型。
- **用法示例**：

```php
Cache::setSessionId('custom_session_id');
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2025-4-25 17点

[返回目录](/SharkyPHP.md)
