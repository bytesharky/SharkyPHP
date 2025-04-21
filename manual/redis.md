# Redis 数据库操作类说明

## 类概述

`Sharky\Core\Redis` 类是一个用于操作 Redis 数据库的工具类，提供了连接管理、主从操作、键值操作等功能。

## 常用方法说明

### 1. `connects($name = 'default')`

- **功能**：获取指定名称的 Redis 连接池实例。若该名称的连接池已存在，则直接返回；否则，根据配置创建新的连接池。
- **参数**：
  - `$name`：连接池名称，默认为 `'default'`。
- **返回值**：`Sharky\Core\Redis\Pool` 实例。
- **示例**：

```php
$pool = Redis::connects('custom');
```

### 2. `slave()`

- **功能**：获取默认连接池的从节点连接。
- **返回值**：从节点连接实例。
- **示例**：

```php
$slaveConnection = Redis::slave();
```

### 3. `master()`

- **功能**：获取默认连接池的主节点连接。
- **返回值**：主节点连接实例。
- **示例**：

```php
$masterConnection = Redis::master();
```

### 4. `prefix($prefix)`

- **功能**：为默认连接池设置键名前缀。
- **参数**：
  - `$prefix`：要设置的键名前缀。
- **返回值**：设置前缀后的连接实例。
- **示例**：

```php
$prefixedConnection = Redis::prefix('myapp_');
```

### 5. `select($db)`

- **功能**：选择默认连接池要使用的 Redis 数据库。
- **参数**：
  - `$db`：数据库编号。
- **返回值**：选择数据库后的连接实例。
- **示例**：

```php
$selectedDbConnection = Redis::select(2);
```

### 6. `set($key, $value)`

- **功能**：在主节点上设置键值对。若配置了 `sticky` 且为 `true`，则设置粘性连接。
- **参数**：
  - `$key`：要设置的键名。
  - `$value`：要设置的值。
- **返回值**：设置操作的结果。
- **示例**：

```php
$result = Redis::set('mykey', 'myvalue');
```

### 7. `get($key)`

- **功能**：从从节点获取指定键的值。
- **参数**：
  - `$key`：要获取值的键名。
- **返回值**：键对应的值。
- **示例**：

```php
$value = Redis::get('mykey');
```

### 8. `delete($key)`

- **功能**：在主节点上删除指定的键。若配置了 `sticky` 且为 `true`，则设置粘性连接。
- **参数**：
  - `$key`：要删除的键名。
- **返回值**：删除操作的结果。
- **示例**：

```php
$result = Redis::delete('mykey');
```

### 9. `exists($key)`

- **功能**：检查从节点上指定的键是否存在。
- **参数**：
  - `$key`：要检查的键名。
- **返回值**：若键存在返回 `true`，否则返回 `false`。
- **示例**：

```php
$exists = Redis::exists('mykey');
```
