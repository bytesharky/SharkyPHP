# `Model` 类使用说明

## 一、概述

 `Model` 类位于 `Sharky\Core` 命名空间下，是一个用于与数据库进行交互的数据模型模块。它提供了一系列方法来执行常见的数据库操作，如查询、插入、更新、删除等，同时支持条件筛选、分组、分页以及对查询结果的处理等功能。通过该类，可以方便地在PHP应用程序中对数据库中的数据进行操作。

## 二、类的初始化与构造函数

在使用 `Model` 类之前，无需手动实例化数据库连接对象，容器会为你管理一个数据库连接，可以通过使用 `Container::getInstance()->make('database')` 方法中妥善处理。

当创建 `Model` 类的子类实例时（通常每个数据库表对应一个 `Model` 子类），构造函数会自动执行以下操作：

1. **确定表名**：如果在子类中未显式指定 `$tableName` 属性的值，构造函数会根据子类的类名自动生成表名。它会截取类名（去除命名空间部分），并将末尾的 `Model` 字符串替换为空字符串，然后转换为小写形式作为表名。例如，若子类名为 `UserModel` ，则默认表名会被设置为 `user` 。

2. **获取数据库实例**：通过 `Container::getInstance()->make('database')` 获取数据库连接实例，并赋值给 `$db` 属性。这一步确保了后续的数据库操作能够通过该实例与数据库进行通信。

3. **获取表字段信息**：调用 `$db->getFields($this->tableName)` 方法获取指定表的字段信息，并将结果存储在 `$fields` 属性中。这些字段信息在后续的操作中，如过滤无效字段等，会起到重要作用。

## 三、常用方法说明

### 1. `where($conditions, $operator = 'AND')` 方法

- **功能**：用于设置查询条件。可以接受多种格式的条件参数，以构建 `WHERE` 子句用于后续的查询操作。
- **参数说明**：
  - `$conditions` ：条件参数，可以是多种格式。例如：
    - 简单的键值对格式 `['字段名', '值']`，会被解析为 `字段名 = '值'` 的条件。
    - 完整的三元组格式 `['字段名', '运算符', '值']`，如 `['age', '>', '18']` ，会按照指定的运算符构建条件。
    - 关联数组格式 `['字段名' => '值',...]` 或 `['字段名' => ['运算符', '值'],...]` 也会被正确解析为相应的条件。
  - `$operator` ：可选参数，默认值为 `'AND'` ，用于指定多个条件之间的连接运算符。如果设置为 `'OR'` ，则表示多个条件之间为“或”的关系。
- **示例**：

 ``` php
$model = new YourModel(); 
$model->where(['name', '=', 'John'])->where(['age', '>', '18']);
// 以上代码构建了查询条件：WHERE name = 'John' AND age > '18'
 ```

### 2. `whereOr($conditions)` 方法

- **功能**：与 `where` 方法类似，但专门用于设置“或”关系的查询条件。实际上它内部调用了 `where` 方法，并将 `$operator` 参数设置为 `'OR'` 。
- **示例**：

 ``` php
$model = new YourModel();
$model->whereOr(['name', '=', 'John'])->whereOr(['name', '=', 'Jane']);
// 构建的查询条件为：WHERE name = 'John' OR name = 'Jane'
 ```

### 3. `beginGroup($operator = 'AND')` 和 `endGroup()` 方法

- **功能**：这两个方法配合使用，用于对查询条件进行分组。 `beginGroup` 方法开始一个新的条件分组， `endGroup` 方法结束当前分组。
- **示例**：

 ``` php
$model = new YourModel();
$model->beginGroup('AND')
      ->where(['age', '>', '18'])
      ->where(['gender', '=', 'male'])
      ->endGroup()
      ->where(['city', '=', 'New York']);
// 构建的查询条件为：WHERE (age > '18' AND gender = 'male') AND city = 'New York'
 ```

### 4. `find($id = null)` 方法

- **功能**：用于根据指定的 `id` 值查找单条记录。如果未传入 `id` 参数，则会查找符合当前设置的查询条件的第一条记录。
- **示例**：

 ``` php
$model = new YourModel();
// 查找id为1的记录
$result = $model->find(1);
if ($result) {
    // 处理找到的记录，例如可以通过$result->属性名 访问记录的属性值
    echo $result->name;
} else {
    echo "未找到符合条件的记录";
}
 ```

- **返回**：该方法返回了一个填充的模型。

### 5. `select($fields = null)` 方法

- **功能**：执行查询操作并返回查询结果。可以指定要查询的字段，如果未指定，则根据 `$filter` 属性的值进行查询（默认查询所有字段）。
- **示例**：

 ``` php
$model = new YourModel();
// 查询所有字段的记录
$results = $model->select();
// 只查询name和age字段的记录
$results = $model->fields('name,age')->select();
foreach ($results as $result) {
    // 处理每条查询结果
    echo $result->name. " - ". $result->age;
}
 ```

- **返回**：该方法返回了一个填充的[集合(Container)](/manual/container.md)。

### 6. `insert($data)` 方法

- **功能**：用于向数据库表中插入数据。可以处理单条插入和批量插入两种情况。
- **示例**：

 ``` php
$model = new YourModel();
// 单条插入数据
$data = ['name' => 'John', 'age' => 25];
$model->insert($data);

// 批量插入数据
$data = [
    ['name' => 'Jane', 'age' => 22],
    ['name' => 'Bob', 'age' => 30]
];
$model->insert($data);
 ```

### 7. `update($data)` 方法

- **功能**：根据当前设置的查询条件更新数据库表中的数据。在更新之前会过滤无效字段，并且要求必须设置了查询条件（否则会抛出异常）。
- **示例**：

 ``` php
$model = new YourModel();
$model->where(['id', '=', 1])
      ->update(['name' => 'New Name', 'age' => 26]);
 ```

### 8. `save($data = [])` 方法

- **功能**：根据模型的当前状态（是否已存在主键值）来决定是执行插入操作还是更新操作。如果模型已经有主键值（通过 `$attributes` 属性判断，通常在查询后会设置该属性），则根据主键生成查询条件并执行更新操作；否则执行插入操作。
- **示例**：

 ``` php
$model = new YourModel();
// 假设已经查询到一条记录并赋值给$model，现在修改记录并保存
$model->name = 'Updated Name';
$model->save();

// 插入一条新记录
$newModel = new YourModel();
$newModel->name = 'New Record Name';
$newModel->save();
 ```

### 9. `delete()` 方法

- **功能**：根据当前设置的查询条件删除数据库表中的记录。同样要求必须设置了查询条件（否则会抛出异常）。
- **示例**：

 ``` php
$model = new YourModel();
$model->where(['id', '=', 1])
      ->delete();
 ```

### 10. `fields($fields)` 方法

- **功能**：用于设置要查询的字段。可以接受字符串（逗号分隔的字段名）或数组形式的字段列表。如果传入空值，则重置为查询所有字段（ `['*']` ）。
- **示例**：

 ``` php
$model = new YourModel();
// 设置查询特定字段
$model->fields('name,age');
// 重置为查询所有字段
$model->fields('');
 ```

### 11. `page($page = 1, $pageSize = 20)` 和 `paginate($fields = null)` 方法

- **功能**： `page` 方法用于设置分页参数，包括页码（从1开始）和每页数量。 `paginate` 方法则根据设置的分页参数执行分页查询，并返回包含分页信息和查询数据的数组。
- **示例**：

 ``` php
$model = new YourModel();
// 设置分页参数并执行分页查询
$model->page(2, 10);
$pageInfo = $model->paginate();
echo "当前页：". $pageInfo['current_page']. ", 每页数量：". $pageInfo['page_size']. ", 总页数：". $pageInfo['total_pages']. ", 数据数量：". count($pageInfo['data']);
 ```

### 12. `limit($limit, $offset = 0)` 方法

- **功能**：用于设置查询结果的限制条件，即指定要返回的记录数量（ `$limit` ）和偏移量（ `$offset` ）。设置该参数后会重置与 `page` 相关的参数。
- **示例**：

 ``` php
$model = new YourModel();
// 限制查询结果只返回前5条记录
$model->limit(5);
// 从第10条记录开始，返回接下来的5条记录
$model->limit(5, 10);
 ```

### 13. `count()` 方法

- **功能**：用于统计符合当前设置的查询条件的记录数量。
- **示例**：

 ``` php
$model = new YourModel();
$count = $model->count();
echo "符合条件的记录数量：". $count;
 ```

### 14. `getLastSql($withParams = true)` 方法

- **功能**：用于获取最后一次执行的SQL语句及其参数信息。如果 `$withParams` 参数为 `true` ，则会将占位符替换为实际的参数值展示完整的SQL语句；如果为 `false` ，则只返回原始的SQL语句。
- **示例**：

 ``` php
$model = new YourModel();
$model->where(['name', '=', 'John'])
      ->select();
$lastSqlInfo = $model->getLastSql();
 ```

### 14. `orderBy` 方法

- **功能** `orderBy` 方法用于对查询结果进行排序，它支持多次调用以添加多个排序条件，也支持一次传入多个字段及其排序方向。可以传入字符串或者数组来指定排序规则。

  当传入字符串时，字符串应包含列名和排序方向，例如 `'column_name DESC'`。

  当传入数组时，既可以是关联数组 `['column1' => 'ASC', 'column2' => 'DESC']`，也可以是索引数组 `['column1 ASC', 'column2 DESC']`。

- **示例**

```php
// 创建 YourModel 实例
$model = new YourModel();

// 示例 1: 一次指定一个排序条件，使用字符串
$result = $model
        ->where('age', '>', 20)
        ->orderBy('age DESC')
        ->select();

// 示例 2: 多次调用 orderBy 添加多个排序条件
$result = $model
        ->where('age', '>', 20)
        ->orderBy('age DESC')
        ->orderBy('name ASC')
        ->select();

// 示例 3: 一次传入多个字段排序，使用关联数组
$result = $model
        ->where('age', '>', 20)
        ->orderBy(['age' => 'DESC', 'name' => 'ASC'])
        ->select();

// 示例 4: 一次传入多个字段排序，使用索引数组
$result = $model
          ->where('age', '>', 20)
          ->orderBy(['age DESC', 'name ASC'])
          ->select();
```

### 15. `groupBy` 方法

- **功能** `groupBy` 方法用于对查询结果进行分组，它支持传入单个列名（字符串形式）或多个列名（数组形式），将查询结果按照指定的列进行分组。

- **示例**

```php
// 创建 YourModel 实例
$model = new YourModel();

// 示例 1: 按单个列分组，使用字符串
$result1 = $model
          ->where('age', '>', 20)
          ->groupBy('country')
          ->select();

// 示例 2: 按多个列分组，使用数组
$result2 = $model
          ->where('age', '>', 20)
          ->groupBy(['country', 'city'])
          ->select();
```

### 16. `jion` 方法

- **功能** `jion` 实现联合查询，支持`Left Join`, `Right Join`, `Inner Join`, `Full Join` 默认是 `Inner Join`

- **示例**

```php
$address = new UserAddress();
$user = new User();

// left Join
return $address->alias('a')->fields(["u.nickname","a.*"])
->Join(['user', 'u', 'left'], 'u.uid = a.uid')
->first();
```

### 17. `leftJoin` 方法

- **功能** `jion` 方法的便捷方法。

### 18. `rightJoin` 方法

- **功能** `jion` 方法的便捷方法。

### 19. `innerJoin` 方法

- **功能** `jion` 方法的便捷方法。

### 20. `fullJoin` 方法

- **功能** `jion` 方法的便捷方法。

## 四、注意事项

1. 在使用 `where` 、 `update` 、 `delete` 等需要基于查询条件的方法时，外为了数据安全，不允许执行无条件更新或删除语句。
2. 当使用 `insert` 方法进行批量插入时，要确保传入的数据格式正确，即二维数组形式，且每个子数组的键名要与表字段对应。
3. 在设置分页参数时，注意 `page` 方法和 `limit` 方法的相互影响，设置了 `limit` 方法后会重置 `page` 相关参数，所以在使用分页功能时要根据实际需求合理选择使用哪种方式设置分页。
4. 对于 `fields` 方法，传入的字段名要确保与数据库表中的实际字段名准确匹配，否则可能无法获取到期望的查询结果。

## 附、模型使用示例

- 2025年4月23日 新增 `jion` 方法以及 `leftJion`、`rightJion`、`innerJoin`、 `fullJoin` 便捷方法实现联合查询。

- 经过简单是验证，可以实现一些联合查询，如果你在构建SQL时出现错误，也欢迎你提出问题

``` php
        // // Model Example

        // $address = new UserAddress();
        // $user = new User();

        // // SELECT 1
        // return $address->alias('a')
        // ->first();

        // // SELECT 2
        // return $address->alias('a')->fields(["u.nickname","a.*"])
        // ->leftJoin(['user', 'u'], 'u.uid = a.uid')
        // ->first();

        // // SELECT 3
        // $userClone = $user->subQuery()->where(["uid" => [">", 1000]])
        // ->select();

        // return $address->alias('a')->fields(["u.nickname","a.*"])
        // ->leftJoin([$userClone, 'u'], 'u.uid = a.uid')
        // ->first();

        // // INSERT INTO 1
        // return $user->alias('u')
        // ->insert(["uid" => 1, "nickname" => "sharky"]);

        // // INSERT INTO 2
        // return $user->alias('u')
        // ->insert([["uid" => 1, "nickname" => "sharky"],["uid" => 2, "nickname" => "whale"]]);

        // // INSERT INTO 3
        // $userClone = $user->subQuery()->fields(["uid", "nickname"])
        // ->where(["uid" => [">", 1000]])->select();
        // return $user->fields(["uid", "nickname"])->insert($userClone);

        // // INSERT INTO 4
        // return $user->alias('u')
        // ->save(["uid" => 1, "nickname" => "sharky"]);

        // // UPDATE 1
        // $data = $user->alias('u')
        // ->first();
        // return $data->save(['nickname'=>'Sharky']);

        // // UPDATE 2
        // return $user->where(["uid" => ["=", 1]])->update(['nickname' => 'Sharky']);
        
        // // UPDATE 3
        // return $user->alias('u')->leftJoin(['user_address', 'a'], "u.uid = a.uid")
        // ->where(['u.uid' => ['=', 1]])
        // ->update([
        //     'nickname' => ['a', 'city']
        // ]);

        // // UPDATE 4
        // return $user->alias('u')->leftJoin(['user_address', 'a'], "u.uid = a.uid")
        // ->where(['u.uid' => ['=', 1]])
        // ->update([
        //     'nickname' => 'a.city'
        // ]);

        // // UPDATE 5
        // $addressClone = $address->subQuery()->fields(["uid", "city"])->where(['uid' => 1])
        // ->select();

        // return $user->subQuery()->alias('u')->leftJoin([$addressClone, 'a'], "u.uid = a.uid")
        // ->where(['u.uid' => ['=', 1]])
        // ->update([
        //     'nickname' => ['a', 'city']
        // ])->getLastSql(true);

        // // DELETE 1
        // return $user->alias('u')
        // ->where(["uid" => 1, "nickname" => "sharky"])
        // ->delete();

        // // DELETE 2
        // return $user->alias('u')
        // ->leftJoin(['user_address', 'a'], 'u.uid = a.uid')
        // ->where(["u.uid" => [">", 10000], "a.city" => "北京市"])
        // ->delete();

        // // COUNT
        // return $user->alias('u')
        // ->leftJoin(['user_address', 'a'], 'u.uid = a.uid')
        // ->where(["u.uid" => [">", 10000]])
        // ->count();

        /*
         * The actually generated SQL code
         * 
         * SELECT * FROM eb_user_address AS a LIMIT 1
         * SELECT `u`.`nickname`, `a`.* FROM eb_user_address AS a LEFT JOIN eb_user AS u ON u.uid = a.uid LIMIT 1
         * SELECT `u`.`nickname`, `a`.* FROM eb_user_address AS a LEFT JOIN (SELECT * FROM eb_user WHERE `uid` > 1000 ) AS u ON u.uid = a.uid LIMIT 1
         * 
         * INSERT INTO eb_user (`uid`, `nickname`) VALUES (?, ?)
         * INSERT INTO eb_user (`uid`, `nickname`) VALUES (?, ?),(?, ?)
         * INSERT INTO eb_user (`uid`, `nickname`) (SELECT `uid`, `nickname` FROM eb_user WHERE `uid` > ?)
         * INSERT INTO eb_user (`uid`, `nickname`) VALUES (?, ?)
         * 
         * UPDATE eb_user SET nickname = ? WHERE `uid` = ?
         * UPDATE eb_user SET nickname = ? WHERE `uid` > ?
         * UPDATE eb_user AS u LEFT JOIN eb_user_address AS a ON u.uid = a.uid SET `u`.`nickname` = `a`.`city` WHERE `u`.`uid` = ?
         * UPDATE eb_user AS u LEFT JOIN eb_user_address AS a ON u.uid = a.uid SET `u`.`nickname` = ? WHERE `u`.`uid` = ?
         * UPDATE eb_user AS u LEFT JOIN (SELECT `uid`, `city` FROM eb_user_address WHERE `uid` = ? ) AS a ON u.uid = a.uid SET `u`.`nickname` = `a`.`city` WHERE `u`.`uid` = ?
         * 
         * DELETE FROM eb_user WHERE `uid` = ? AND `nickname` = ?
         * DELETE u FROM eb_user AS u LEFT JOIN eb_user_address AS a ON u.uid = a.uid WHERE `u`.`uid` > ? AND `a`.`city` = ?
         * 
         * SELECT COUNT(*) as total FROM eb_user AS u LEFT JOIN eb_user_address AS a ON u.uid = a.uid WHERE `u`.`uid` > ?
         * 
         */
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点

[返回目录](/SharkyPHP.md)
