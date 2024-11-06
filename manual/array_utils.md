# `ArrayUtils`类使用说明

## 一、概述

`ArrayUtils`类位于`Sharky\Utils`命名空间下，是一个提供多维数组操作相关实用功能的工具类。它包含了用于深度合并数组、判断数组是否为关联数组、获取数组指定路径的值以及设置数组指定路径的值等方法，方便在处理多维数组时进行常见的操作。

## 二、主要方法说明

### 1. `deepMerge(...$arrays): array`方法

- **功能**：用于深度合并多个数组。它会遍历传入的每个数组，根据键的类型和是否已存在于结果数组中来决定如何合并值。如果键是整数，直接将值添加到结果数组末尾；如果键是字符串且对应的值在结果数组和当前要合并的数组中都是数组，就递归调用`deepMerge`方法进行深度合并；否则，直接将当前值赋给结果数组中对应的键。
- **参数说明**：
  - `...$arrays`：可变参数，接受要合并的多个数组列表。
- **使用示例**：

```php
use Sharky\Utils\ArrayUtils;

$array1 = [
    'key1' => 'value1',
    'key2' => [
        'subkey1' => 'subvalue1'
    ]
];

$array2 = [
    'key2' => [
        'subkey2' => 'subvalue2'
    ],
    'key3' => 'value3'
];

$resultArray = ArrayUtils::deepMerge($array1, $array2);
// 输出合并后的数组
print_r($resultArray);
```

### 2. `isAssoc(array $array): bool`方法

- **功能**：用于判断一个数组是否为关联数组。通过比较数组的键与从0到数组长度减1的连续整数序列是否相同来判断。如果数组为空，返回`false`；否则，如果键与连续整数序列不同，就判定为关联数组，返回`true`；如果键与连续整数序列相同，判定为非关联数组，返回`false`。
- **参数说明**：
  - `array $array`：要检查的数组。
- **使用示例**：

```php
use Sharky\Utils\ArrayUtils;

$array1 = [
    'name' => 'John',
    'age' => 25
];

$isAssocArray1 = ArrayUtils::isAssoc($array1);
// 输出: true，因为是关联数组

$array2 = [1, 2, 3];

$isAssocArray2 = ArrayUtils::isAssoc($array2);
// 输出: false，因为是索引数组
```

### 3. `get(array $array, $path, $default = null): mixed`方法

- **功能**：用于获取数组指定路径的值。可以接受点号分隔的字符串路径或数组形式的路径。它会按照路径中的键依次在数组中查找，如果在某一步找不到对应的键或者数组不是数组类型，就返回默认值；如果成功遍历完路径，就返回最终找到的值。
- **参数说明**：
  - `array $array`：要从中获取值的数组。
  - `$path`：可以是点号分隔的字符串路径，如`'key1.key2.subkey'`，也可以是数组形式的路径，如`['key1', 'key2', 'subkey']`。
  - `$default`：可选参数，当找不到指定路径的值时返回的默认值，默认为`null`。
- **使用示例**：

```php
use Sharky\Utils\ArrayUtils;

$array = [
    'user' => [
        'name' => 'John',
        'address' => [
            'city' => 'New York',
            'street' => '123 Main St'
        ]
    ]
];

$name = ArrayUtils::get($array, 'user.name', 'Guest');
// 输出: 'John'，因为找到了指定路径的值

$phone = ArrayUtils::get($array, 'user.phone', 'N/A');
// 输出: 'N/A'，因为指定路径不存在，返回默认值
```

### 4. `set(array &$array, $path, $value): void`方法

- **功能**：用于设置数组指定路径的值。同样可以接受点号分隔的字符串路径或数组形式的路径。它会按照路径中的键在数组中逐步创建必要的子数组，直到到达指定路径的最后一个键，然后将给定的值赋给该键。
- **参数说明**：
  - `array &$array`：要修改的数组，注意这里是引用传递，以便能直接修改原数组。
  - `$path`：可以是点号分隔的字符串路径，如`'key1.key2.subkey'`，也可以是数组形式的路径，如`['key1', 'key2', 'subkey']`。
  - `$value`：要设置的值。
- **使用示例**：

```php
use Sharky\Utils\ArrayUtils;

$array = [
    'user' => [
        'name' => 'John'
    ]
];

ArrayUtils::set($array, 'user.address.city', 'Los Angeles');

print_r($array);
// 输出修改后的数组，会新增 'user.address' 子数组，并设置 'city' 键的值为 'Los Angeles'
```

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点
