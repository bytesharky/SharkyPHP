# `Container` 类使用说明

## 一、概述

 `Container` 类位于 `Sharky\Core` 命名空间下，是一个简单容器模块。它主要用于管理对象的创建和依赖注入，通过绑定抽象类型与具体实现，能够方便地创建类的实例，并自动解析和注入所需的依赖关系。

## 二、使用步骤

### 1. 获取容器实例

首先，需要获取 `Container` 类的实例，整个应用程序通常只需一个容器实例，可通过以下方式获取：

 ``` php
$container = Container::getInstance();
 ```

### 2. 绑定抽象类型与具体实现（bind方法）

使用 `bind` 方法来建立抽象类型和具体实现之间的绑定关系。

 ``` php
$container->bind($abstract, $concrete = '', $parameters = []);
 ```

- **参数说明**：
  - `$abstract` ：必需，代表抽象类型名，可以是一个简单的字符串标识，用于后续通过该抽象类型获取对应的具体实现实例。
  - `$concrete` ：可选，默认为空字符串。如果为空，则默认与 `$abstract` 相同。它可以是具体的类名（字符串形式）或者一个闭包函数（用于动态创建实例并处理依赖注入）。
  - `$parameters` ：可选，默认为空数组。用于传递给具体实现（类的构造函数或闭包函数）的参数。如果 `$concrete` 传入的是数组且 `$parameters` 为空，那么就将 `$concrete` 视为参数。

示例：

 ``` php

// 绑定接口与具体类实现
$container->bind('app', 'Shark\\Corer\\App');

// 使用闭包函数作为具体实现，并传递参数
$container->bind('Mailer', function ($container, $parameters) {
    return new Mailer($parameters['host'], $parameters['port']);
}, ['host' => 'smtp.example.com', 'port' => 25]);
 ```

### 3. 创建类的实例并注入依赖（make方法）

通过 `make` 方法根据绑定的抽象类型创建对应的类实例，并自动解析和注入所需的依赖关系。

 ``` php
$instance = $container->make($abstract, array $parameters = []);
 ```

- **参数说明**：
  - `$abstract` ：必需，之前通过 `bind` 方法绑定过的抽象类型名。
  - `$parameters` ：可选，默认为空数组。用于传递给具体实现（类的构造函数或闭包函数）的额外参数，这些参数会与绑定过程中预设的参数合并。

示例：
假设已经绑定了 `config` ：

 ``` php
$config= $container->make('config');
 ```

如果类的构造函数有额外需要传递的参数，可以这样做：

 ``` php
$parameters = ['dbConnection' => $dbConnection];
$database = $container->make('database', $parameters);
 ```

### 4. 通过 `get` 方法获取实例

 `get` 方法与 `make` 方法功能完全相同的。

 ``` php
$instance = $container->get($abstract, array $parameters = []);
 ```

## 三、注意事项

### 1. 绑定关系的正确性

在使用 `bind` 方法时，确保传入的抽象类型和具体实现符合要求。具体实现必须是一个类名（对应的类要存在）或者是一个闭包函数，否则会抛出异常。

### 2. 参数传递

当通过 `make` 或 `get` 方法创建实例时，如果类的构造函数有参数要求，要注意参数的传递方式。对于有类型提示的参数，容器会尝试通过查找对应的绑定关系来获取实例并注入；对于没有类型提示且没有默认值的参数，如果没有在调用 `make` 或 `get` 方法时传入相应的值，会抛出异常。

### 3. 类的存在性

无论是在绑定过程中指定的具体类名，还是通过自动检测构造函数参数等操作涉及到的类，都要确保这些类在当前应用程序环境中是存在的，否则会抛出异常。

---

本文档是在 AI 生成的内容的基础上修订，其信息不保证完全准确。

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2024-11-6 22点

[返回目录](/SharkPHP.md)
