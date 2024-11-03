<?php

/**
 * @description 框架友好错误页面
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出错了！</title>
    <style>
    body{font-family:Arial,sans-serif;background-color:#f4f4f4;color:#333;text-align:center;padding:50px 0}
    h1{font-size:2.5em;color:#e74c3c}
    p{font-size:1.2em}
    .button{display:inline-block;padding:10px 20px;margin-top:20px;background-color:#3498db;
    color:#fff;text-decoration:none;border-radius:5px;transition:background-color .3s}
    .button:hover{background-color:#2980b9}
    .copyright {font-size: 14px; color: #999;}
    .copyright p {margin: 5px 0}    
    </style>
</head>
<body>
    <h1>哎呀，出错了！</h1>
    <p>我们遇到了一个问题，请稍后再试。</p>
    <p>如果这个问题持续存在，请联系支持团队。</p>
    <a href="/" class="button">返回首页</a>
    <hr>
    <div class="copyright">
    <P><?PHP echo(PROJECT . ' Version ' . VERSION);?></p>
    <P><?PHP echo(COPYRIGHT);?></p>
    </div>
</body>
</html>
