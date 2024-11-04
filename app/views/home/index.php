<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎使用SharkyPHP</title>
    <style>
        body{color:#666;font-family:Arial,sans-serif;background-color:#f4f4f4;
            padding-top:50px;display:flex;flex-direction:column;align-items:center;justify-content:center;}
        p{text-align:center;}
        h1{color:#333;font-size:36px;margin-bottom:20px}
        .logo{width:150px;height:150px;margin-bottom:30px;background-image:url(/assets/images/sharky.svg);
            background-size:cover;background-position:center;border-radius:50%;box-shadow:0 0 10px rgba(0,0,0,.2)}
        a.link{color:#666;font-size:18px;text-align:center;margin:0 10px}
        pre{word-wrap:break-word;white-space:pre-wrap;text-align:left;
            margin: 1rem 2rem;font-size: 14px;line-height: 1.5;min-height: 200px;}
        .content{width: 100%;max-width:500px;}
        .content p{margin:1rem 2rem;}
        .version {width: 90%; border-top: 1px solid #aaa;font-size: 14px;}
    </style>
</head>

<body>
    <div class="logo"></div>
    <h1>{{ $title }}</h1>
    <h2>欢迎使用SharkyPHP</h2>
    <div class="content">
        <div class="nav">
            <a class="link" href="/">Index</a>
            <a class="link" href="/about">About</a>
            <a class="link" href="/view">View</a>
            <a class="link" href="/database">Database</a>
            <a class="link" href="https://github.com/bytesharky/sharkphp" target="_blank">Github</a>
        </div>
        <pre>{{ $content }}</pre>
        {% if $html??false %}
        <p><a class="link" href="/demo/list">查看 Twig Demo</a></p>
        {% endif %}
    </div>
    <hr>
    <div class="version">
        <p>{{ PROJECT }} Version {{ VERSION }}</p>
        <p>{{ COPYRIGHT }}</p>  
    </div>
</body>

</html>
