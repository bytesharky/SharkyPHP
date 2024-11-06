# `Database`类使用说明

## 一、概述

`Database`类位于`Sharky\Core`命名空间下，是用于与数据库进行交互的核心类。它能够根据配置信息建立数据库连接（支持`mysqli`和`PDO`两种连接方式），并提供了一系列方法来执行常见的数据库操作，如查询数据、执行SQL语句、获取表字段信息、管理事务以及获取最后插入记录的ID等。

## 二、类的初始化

通过`Database`类的构造函数来完成类的初始化操作，主要是建立与数据库的连接。

### `__construct()`方法

- **功能**：在构造函数内部，首先通过`Container`类的实例获取`config`实例，然后从配置中获取与数据库相关的配置信息（`database`部分的配置）。接着，根据获取到的`connect_type`配置项的值来确定使用哪种数据库连接方式（`mysqli`或`PDO`），最后调用`connect`方法并传入完整的数据库配置信息来建立数据库连接。

## 三、主要方法说明

### 1. `connect($config)`方法

- **功能**：此方法用于建立与数据库的实际连接。根据类的`$connectType`属性值（在构造函数中根据配置确定）来选择使用`mysqli`或`PDO`方式进行连接。
- **`mysqli`连接方式**：
  - 如果`$connectType`为`mysqli`，则使用`mysqli`类的构造函数创建一个新的`mysqli`对象，传入数据库主机、用户名、密码、数据库名和端口等必要参数。如果连接过程中出现错误（通过`$connection->connect_error`判断），则抛出一个异常，提示连接失败及具体错误信息。成功连接后，还会设置数据库连接的字符集为配置中指定的字符集（`$config['db_charset']`）。
- **`PDO`连接方式**：
  - 如果`$connectType`为其他值（这里主要针对`PDO`连接），首先会拼接出`DSN`（数据源名称）字符串，包含数据库主机、端口、数据库名和字符集等信息。然后使用`PDO`类的构造函数创建一个新的`PDO`对象，传入`DSN`、用户名、密码以及一些设置`PDO`属性的参数（如设置错误模式为抛出异常，设置默认的获取结果模式为关联数组形式）。如果在创建`PDO`连接过程中出现错误（通过捕获`PDOException`异常判断），则抛出一个异常，提示连接失败及具体错误信息。

### 2. `query($sql, $params = [])`方法

- **功能**：用于执行查询语句并返回查询结果。根据当前使用的数据库连接类型（`mysqli`或`PDO`）来执行不同的操作。
- **`mysqli`连接方式**：
  - 如果`$connectType`为`mysqli`，首先使用`$connection->prepare`方法准备要执行的查询语句。如果传入了查询参数（`$params`不为空），则通过`bind_param`方法绑定参数，根据参数数量生成相应的类型字符串（如全是字符串类型则为`sss...`）并绑定参数值。然后执行查询语句，通过`$stmt->get_result`获取查询结果集，最后使用`fetch_all`方法以关联数组形式（`MYSQLI_ASSOC`）获取所有查询结果并返回。
- **`PDO`连接方式**：
  - 如果`$connectType`为其他值（主要针对`PDO`），首先使用`$connection->prepare`方法准备查询语句，然后通过`$pdoStatement->execute`方法执行查询语句并传入查询参数（`$params`），最后使用`fetchAll`方法获取所有查询结果并返回。

### 3. `execute($sql, $params = [])`方法

- **功能**：用于执行非查询语句（如插入、更新、删除等操作）并返回受影响的行数。同样根据数据库连接类型执行不同操作。
- **`mysqli`连接方式**：
  - 如果`$connectType`为`mysqli`，首先准备要执行的语句，绑定参数（如果有参数），然后执行语句，最后返回受影响的行数（通过`$stmt->affected_rows`获取）。
- **`PDO`连接方式**：
  - 如果`$connectType`为其他值（主要针对`PDO`），准备语句，执行语句并传入参数，最后返回受影响的行数（通过`$pdoStatement->rowCount()`获取）。

### 4. `getFields($table)`方法

- **功能**：用于获取指定数据库表的所有字段名。根据连接类型的不同，执行方式略有不同。
- **`mysqli`连接方式**：
  - 如果`$connectType`为`mysqli`，首先执行`SHOW COLUMNS FROM {$table}`查询语句获取表的字段信息结果集，然后通过循环遍历结果集，将每个字段的`Field`属性值（即字段名）添加到一个数组中，最后返回该数组。
- **`PDO`连接方式**：
  - 如果`$connectType`为其他值（主要针对`PDO`），执行同样的`SHOW COLUMNS FROM {$table}`查询语句获取结果集，然后使用`array_column`函数直接从结果集的所有行中提取`Field`属性值（即字段名）并返回。

### 5. `beginTransaction()`、`commit()`、`rollback()`方法

- **功能**：这三个方法分别用于开启事务、提交事务和回滚事务。根据连接类型的不同，会调用相应数据库连接对象的对应方法来执行这些操作。无论是`mysqli`连接还是`PDO`连接，都会直接调用对应的数据库连接对象的相关方法（如`$connection->begin_transaction()`、`$connection->commit()`、`$connection->rollback()`）来完成相应事务操作。

### 6. `runTransaction(callable $callBack)`方法

- **功能**：用于执行一个事务操作，它会先开启事务，然后调用传入的回调函数（`$callBack`）并传入`Database`类的实例自身作为参数。如果回调函数执行成功（返回`true`），则提交事务并返回`true`；如果回调函数执行失败（返回`false`）或在执行过程中出现异常，则回滚事务并返回`false`。

### 7. `lastInsertId()`方法

- **功能**：用于获取最后插入记录的ID。根据连接类型的不同，会调用相应数据库连接对象的对应方法来获取该ID。如果`$connectType`为`mysqli`，则返回`$connection->insert_id`；如果为其他值（主要针对`PDO`），则返回`$connection->lastInsertId()`。

### 8. `__destruct()`方法

- **功能**：这是析构函数，在对象被销毁时执行。如果数据库连接对象存在（`$connection`不为空），根据连接类型进行相应处理。如果是`mysqli`连接，则调用`$connection->close()`关闭连接；如果是`PDO`连接，则将`$connection`设置为`null`，释放相关资源。

## 四、使用步骤

### 1. 创建`Database`类的实例

```php
$database = new Database();
```

### 2. 根据需要执行相应的数据库操作，例如

- **执行查询操作**：

```php
$sql = "SELECT * FROM users";
$result = $database->query($sql);
foreach ($result as $row) {
    // 处理查询结果，如输出用户信息
    echo $row['name']. ", ". $row['age'];
}
```

- **执行非查询操作（如插入）**：

```php
$sql = "INSERT INTO users (name, age) VALUES ('John', 25)";
$affectedRows = $database->execute($sql);
if ($affectedRows > 0) {
    echo "插入成功，受影响行数: ". $affectedRows;
} else {
    echo "插入失败";
}
```

- **获取表字段信息**：

```php
$table = "users";
$fields = $database->getFields($table);
foreach ($fields as $field) {
    echo $field. ", ";
}
```

- **执行事务操作**：

```php
$database->runTransaction(function ($db) {
    // 在回调函数中执行一系列数据库操作，如插入和更新
    $sql1 = "INSERT INTO users (name, age) VALUES ('Jane', 30)";
    $sql2 = "UPDATE users SET age = age + 1 WHERE name = 'John'";
    $db->execute($sql1);
    $db->execute($sql2);
    return true;
});
```

## 五、数据库配置文件

在`app/config`目录下创建`database.php`文件，并return一个数组；

配置文件示例

```php
return [
    'connect_type' =>'mysqli', // 或者 'PDO'，根据实际需求选择数据库连接类型
    'db_host' => 'localhost', // 数据库主机地址
    'db_user' => 'your_username', // 数据库用户名
    'db_pass' => 'your_password', // 数据库密码
    'db_name' => 'your_database_name', // 数据库名称
    'db_port' => '3306', // 数据库端口号，MySQL默认是3306，可根据实际情况修改
    'db_charset' => 'utf8mb4' // 数据库字符集，推荐使用utf8mb4以支持更多字符
];
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点
