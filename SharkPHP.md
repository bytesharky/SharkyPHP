# SharkyPHP

寄语：

SharkyPHP 是一款独具特色的超迷你 MVC 框架。虽然在中大型项目开发中，SharkyPHP 可能不是最佳选择，但如果你热衷于探索 MVC 框架的实现原理，或者投身于小微型项目的开发，那么 SharkyPHP 将是一个理想的伴侣。当引入一个完整的 MVC 框架显得臃肿不堪时，不妨试试 SharkyPHP，它或许能为你带来轻松愉快的开发体验。

同时，SharkyPHP 是一个开放的作品，就像一座等待雕琢的美玉。我们热忱欢迎每一位开发者积极参与，为它提出宝贵的优化建议。让我们共同打磨 SharkyPHP，使其在小微型项目的天空中绽放更加璀璨的光芒。

—— ByteSharky

## 一、项目结构

```css
project/
│
├── public/                  // 网站根目录
│   └── index.php            // 主入口
│
├── sharky/                  // 框架目录
│   ├── bootstrap.php        // 启动脚本
│   │ 
│   ├── core/                // 核心目录
│   │   ├── App.php          // 框架核心
│   │   ├── Config.php       // 配置管理
│   │   ├── Container.php    // 简单容器
│   │   ├── Controller.php   // 控制器基类
│   │   ├── Model.php        // 模型基类
│   │   └── Router.php       // 路由管理
│   │ 
│   ├── libs/                // 扩展库
│   │   ├── Cookie.php       // 状态管理器（Cookie）
│   │   ├── Session.php      // 会话管理器（Session）
│   │   ├── Database.php     // 数据库管理器
│   │   ├── Controller.php   // 控制器基类
│   │   ├── Model.php        // 模型基类
│   │   └── ...
│   │
│   ├── configs/             // 配置目录
│   │   ├── config.php       // 配置文件
│   │   └── ...
│   │
│   ├── routes/              // 路由目录
│   │   ├── routes.php       // 路由文件
│   │   └── ...
│   │
│   └── errors/              // 错误模板文件夹
│       ├── 403.php
│       ├── 404.php
│       ├── 405.php
│       └── 500.php
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
    └── config/              // 配置文件夹
        ├── redis.php        // Redis配置文件
        ├── database.php     // 数据库配置文件
        └── ...
```

## 二、使用说明

1. ### 下载安装

2. ### Hello Word

3. ### 配置文件

   #### 配置文件的加载

   所有的配置文件都放在`project/app/config/`目录下，框架会自动加载他们。

   ```css
   project/
   └── app/
       └── config/
           ├── redis.php        // Redis配置文件
           ├── database.php     // 数据库配置文件
           └── ...
   ```

   #### 配置文件的读取

4. ### 控制器

5. ### 模型

6. ### 视图

7. ### 会话

8. ### 路由

9. ### 伪静态

   nginx请参考下方配置，具体以实际情况为准

   ````nginx
   ````

   apache请参考下方配置，具体以实际情况为准

   在 `public/` 目录中创建 `.htaccess` 文件，配置 URL 重写规则来隐藏

   ````nginx
   Options -Indexes
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
   ````

10. ### 结束
