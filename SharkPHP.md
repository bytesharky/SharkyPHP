# SharkyPHP

寄语：

SharkyPHP 是一款独具特色的超迷你 MVC 框架。虽然在中大型项目开发中，SharkyPHP 可能不是最佳选择，但如果你热衷于探索 MVC 框架的实现原理，或者投身于小微型项目的开发，那么 SharkyPHP 将是一个理想的伴侣。当引入一个完整的 MVC 框架显得臃肿不堪时，不妨试试 SharkyPHP，它或许能为你带来轻松愉快的开发体验。

同时，SharkyPHP 是一个开放的作品，就像一座等待雕琢的美玉。我们热忱欢迎每一位开发者积极参与，为它提出宝贵的优化建议。让我们共同打磨 SharkyPHP，使其在小微型项目的天空中绽放更加璀璨的光芒。

—— ByteSharky

## 一、项目结构

### 1. 核心目录架构

```css
project/
│
├── public/                  // 网站根目录
│   └── index.php            // 主入口
│
└── sharky/                  // 框架目录
    ├── bootstrap.php        // 启动脚本
    ├── common.php           // 全局函数
    ├── constants.php        // 全局常量  
    │ 
    ├── core/                // 核心目录
    │   ├── App.php          // 框架核心
    │   ├── Collection.php   // 数据集合
    │   ├── Config.php       // 配置管理
    │   ├── Container.php    // 简单容器
    │   ├── Controller.php   // 控制器基类
    │   ├── Database.php     // 数据库管理器
    │   ├── Exception.php    // 异常类
    │   ├── Model.php        // 模型基类
    │   └── Router.php       // 路由管理
    │ 
    ├── libs/                // 扩展库
    │   ├── Cookie.php       // 状态管理器（Cookie）
    │   ├── Session.php      // 会话管理器（Session）
    │   ├── Template.php     // 模板类
    │   └── ...
    │ 
    ├── utils/               // 工具目录
    │   ├── ArrayUtils.php   // 多维数组工具
    │   └── ...
    │
    ├── configs/             // 配置目录(核心、公共、多站点配置必须在此处)
    │   ├── config.php       // 配置文件
    │   └── ...
    │
    └── errors/              // 错误模板文件夹
        ├── 404.php
        └── ...
```

### 2. 应用目录

#### 2.1. 单站点模式

```css
project/
│ 
└── app/                     // 应用目录
    ├── controllers/         // 控制器文件夹
    │   ├── HomeController.php
    │   └── ...
    │
    ├── models/              // 模型文件夹
    │   ├── HomeModel.php
    │   └── ...
    │
    ├── views/               // 视图文件夹
    │   ├── demo/
    │   │   ├── index.html
    │   │   └── ...
    │   └── ...
    │
    ├── routes/              // 路由目录
    │   ├── routes.php       // 路由文件
    │   └── ...
    │
    └── config/              // 配置文件夹(自动覆盖Sharky下的配置)
        ├── redis.php        // Redis配置文件
        ├── database.php     // 数据库配置文件
        └── ...
```

#### 2.2. 多站点模式

```css
project/
│ 
└── app/                     // 应用目录
    ├── website1/            // 控制器文件夹
    │   ├── controllers/     // 控制器文件夹
    │   ├── models/          // 模型文件夹
    │   ├── views/           // 视图文件夹
    │   ├── config/          // 配置文件夹
    │   ├── cache/           // 缓存文件
    │   └── ...
    │
    ├── website2
    └── ...
```

## 二、环境要求

   1. 支持Linux和Windows
   2. 目前尚不确定最低支持的PHP版本(建议8.0++)
   3. 确保安装了对应版本的`composer`包管理器
   4. **本框架不依赖其他第三方包**
   5. 建议使用composer加载类
   6. **注意：**

>
> 此框架引入了Twig模版引擎，位于`/controllers/TwigController.php`。(从2024年11月6日后不在默认安装Twig)
>
> 如需使用请通过`composer require twig/twig`命令安装。
>

## 三、使用说明

1. ### 下载安装

   * **Github**    ：<https://github.com/bytesharky/SharkPHP>

   * **Gitee**     ：<https://gitee.com/bytesharky/SharkPHP> (可能更新不同步)

   本框架不依赖其他第三方包，下载后需要运行`composer dump-autoload`生成自动加载类的脚本。

2. ### 核心

   [`App`](/manual/app.md) 类位于`Sharky\Core`命名空间下，是框架的核心类，负责整个应用程序的初始化和启动流程。它通过依赖注入获取`Router`和`Config`实例，完成站点配置的加载以及路由的派发等关键操作，从而驱动整个应用程序运行。

3. ### 容器

   [Container](/manual/container.md) 类位于`Sharky\Core`命名空间下，是一个简单容器模块。它主要用于管理对象的创建和依赖注入，通过绑定抽象类型与具体实现，能够方便地创建类的实例，并自动解析和注入所需的依赖关系。

4. ### 配置文件*

   [`Config`](/manual/config.md) 类位于 `Sharky\Core` 命名空间下，是框架中的配置管理器模块。它主要负责加载框架核心配置文件以及站点配置文件，并提供了根据指定路径获取配置值的功能。通过该类，可以方便地在应用程序中统一管理和获取各种配置信息。

   #### 配置文件的加载

    所有需要复用的配置，以及多站点配置，必须放在`sharky/core/config/`目录下面，框架会自动加载他们。

    所有的应用配置文件都应放在`project/app/config/`目录下面，框架会自动加载他们。

    如果开启了多站点则是在`project/app/subsite/config/`目录下面，同样框架会自动加载他们。

    ```php
    project/
    └── app/
        └── config/
            ├── redis.php        // Redis配置文件
            ├── database.php     // 数据库配置文件
            └── ...
   
    ```

   #### 配置文件的读取

    我们通过点分路径的方式读取配置，下面是一个示例

    ```php
        // 首先我要从容器中获取一个config抽象类
        use Sharky\Core\Container;
        $container = Container::getInstance();
        $config = $container->make('config');
   
        // 然后用这个抽象类的get方法
        // get($path, $default = null)
        $isdebug = $config->get('config.isdebug', false);
    ```

5. ### 控制器*

   [`Controller`](/manual/controller.md) 类位于`Sharky\Core`命名空间下，是框架中的控制器模块，主要负责处理与业务逻辑相关的操作，如获取配置信息以及在出现错误时渲染相应的错误页面等。

6. ### 模型*

   [`Database`](/manual/database.md) 类位于`Sharky\Core`命名空间下，是用于与数据库进行交互的核心类。它能够根据配置信息建立数据库连接（支持`mysqli`和`PDO`两种连接方式），并提供了一系列方法来执行常见的数据库操作，如查询数据、执行SQL语句、获取表字段信息、管理事务以及获取最后插入记录的ID等。

   [`Mode`](/manual/mode.md)类位于 `Sharky\Core` 命名空间下，是一个用于与数据库进行交互的数据模型模块。它提供了一系列方法来执行常见的数据库操作，如查询、插入、更新、删除等，同时支持条件筛选、分组、分页以及对查询结果的处理等功能。通过该类，可以方便地在PHP应用程序中对数据库中的数据进行操作。

   [`Collection`](/manual/collection.md) 类位于 `Sharky\Core` 命名空间下，实现了 `Iterator` 接口，主要用于对一组数据项进行管理和操作，提供了诸多便捷的方法来处理集合内的数据，比如添加元素、转换为数组、应用回调函数到每个元素以及遍历集合等操作。`Model` 类电的查询结果会返回此类。

7. ### 视图*

   [`Template`](/manual/template.md) 类位于 `Sharky\Libs` 命名空间下，是一个模板引擎类，它通过提供了一系列功能用于加载模板、处理模板中的变量、指令等，并进行渲染输出。主要特性包括支持多语言翻译、模板编译缓存以及多种常见的模板指令处理，如变量输出、继承、块定义、条件判断和循环等。

8. ### 会话*

   [`Session`](/manual/session.md) 类位于 `Sharky\Libs` 命名空间下，是一个用于管理Session操作的工具类，它提供了一系列便捷的方法来处理会话相关的操作，如启动会话、设置会话变量、获取会话变量值、检查变量是否存在、删除特定变量以及销毁整个会话等。

   [`Cookie`](/manual/cookie.md) 类位于 `Sharky\Libs` 命名空间下，是一个用于管理Cookie操作的工具类。它提供了一系列便捷的方法来设置、获取、检查和删除Cookie，使得在PHP应用程序中对Cookie的处理更加简单和规范化，遵循了良好的编程实践。

9. ### 路由*

   [`Route`](/manual/route.md) 命名空间下，是框架中的路由管理模块，主要负责加载路由文件、注册路由及路由分组、格式化路径以及根据请求的方法和URI来派遣路由，从而确定要执行的相应控制器方法或回调函数。

10. ### 伪静态*

    nginx请参考下方配置，具体以实际情况为准

    ````nginx
     server {
         listen       80;
         server_name  example.com www.example.com;
         charset      utf-8;
      
         root    /var/www/example.com;
         
         location ~ / {
             index                  index.php;
             try_files $uri $uri/  /index.php?$query_string;
             client_max_body_size         50m; 
             client_body_buffer_size     256k; 
         }
      
         location = /index.php {
             fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
             fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
             include fastcgi_params;
             fastcgi_intercept_errors on;
         }
     }
    ````

    apache请参考下方配置，具体以实际情况为准

    确保LoadModule php_module modules/libphp.so等类似模块已启用

    ```apache
    <VirtualHost *:80>
        ServerName example.com
        ServerAlias www.example.com
        DocumentRoot "/var/www/example.com"

        <Directory "/var/www/example.com">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>

        <FilesMatch \.php$>
            SetHandler application/x-php
        </FilesMatch>

        RewriteEngine On
        RewriteBase /

        RewriteCond %{REQUEST_FILENAME}!-f
        RewriteCond %{REQUEST_FILENAME}!-d
        RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
    </VirtualHost>

    ```

    或在 `public/` 目录中创建 `.htaccess` 文件，配置 URL 重写规则来隐藏

    ````apache
    Options -Indexes
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
    ````

11. ### 其他

    [`Exception`](/manual/exception.md) 类位于`Sharky\Core`命名空间下，它继承自`\Exception`类，主要用于统一处理应用程序中的异常和错误信息。通过设置全局的异常处理函数和错误处理函数，能够根据配置文件中的调试模式设置来决定如何展示错误信息，并且在出现异常或错误时渲染相应的错误页面。

    [`Array_Utils`](/manual/array_utils.md) 类位于`Sharky\Utils`命名空间下，是一个提供多维数组操作相关实用功能的工具类。它包含了用于深度合并数组、判断数组是否为关联数组、获取数组指定路径的值以及设置数组指定路径的值等方法，方便在处理多维数组时进行常见的操作。
