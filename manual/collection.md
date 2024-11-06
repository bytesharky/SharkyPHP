# `Collection` 类使用说明

## 一、概述

`Collection` 类位于 `Sharky\Core` 命名空间下，实现了 `Iterator` 接口，主要用于对一组数据项进行管理和操作，提供了诸多便捷的方法来处理集合内的数据，比如添加元素、转换为数组、应用回调函数到每个元素以及遍历集合等操作。`Model` 类电的查询结果会返回此类。

## 二、方法使用说明

### 1. `toArray()` 方法

- **功能**：将集合中的所有元素转换为数组形式。如果集合中的元素是 `Model` 类的实例，那么会先调用该实例的 `toArray()` 方法将其转换为数组，之后再将所有转换后的元素组成新的数组返回。
- **示例**：

```php
$userModel = new userModel();
$collection = $userModel->where([ "age" = >30 ])->select();
$arrayData = $collection->toArray();
// 此时$arrayData就是包含转换后数据的数组，可按需进行后续操作，比如打印查看
print_r($arrayData);
```

### 2. `map(callable $callback)` 方法

- **功能**：针对集合中的每一个元素应用指定的回调函数，并返回一个新的集合，新集合中包含的是经过回调函数处理后的各个元素。
- **示例**：

```php
$collection = new Collection([1, 2, 3]);

// 定义一个回调函数，这里示例是将每个元素加10
$callback = function ($item) {
    return $item + 10;
};

$newCollection = $collection->map($callback);

// 可以获取新集合的所有元素进行查看
print_r($newCollection->all());
```

### 3. `add($item)` 方法

- **功能**：向集合中添加单个元素。可以添加任意类型的元素到集合里，添加完成后，该元素就成为集合中的一员。
- **示例**：

```php
$collection = new Collection();

$item = 'new element';
$collection->add($item);

// 查看添加元素后的集合所有元素
print_r($collection->all());
```

### 4. `all()` 方法

- **功能**：获取集合中的所有元素，并以数组的形式返回这些元素，方便对集合内的数据进行查看、遍历等其他操作。
- **示例**：

```php
$collection = new Collection([1, 2, 3]);

$allItems = $collection->all();
// 可以对获取到的数组进行操作，比如打印
print_r($allItems);
```

### 5. `count()` 和`count` 属性方法

- **功能**：返回集合中元素的数量，可用于了解集合的规模大小等情况。
- **示例**：

```php
$collection = new Collection([1, 2, 3]);

echo $collection->count();

echo $collection->count;
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点
