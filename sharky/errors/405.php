<?php

/**
 * @description 自定义错误页面
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 - 方法不被允许</title>
    <style>
        .copyright {font-size: 14px; color: #999;}
        .copyright p {margin: 5px 0;}
    </style>
</head>
<body>
    <h1>405 - 方法不被允许</h1>
    <p>抱歉，您正在使用的方法对于此URL是不被允许的。</p>
    <hr>
    <div class="copyright">
    <P><?PHP echo(PROJECT . ' Version ' . VERSION);?></p>
    <P><?PHP echo(COPYRIGHT);?></p>
    </div>
</body>
</html>
