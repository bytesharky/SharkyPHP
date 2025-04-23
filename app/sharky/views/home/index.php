<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎使用SharkyPHP</title>
    <style>
        body{color:#666;font-family:Arial,sans-serif;background-color:#f4f4f4;
            padding-top:50px;display:flex;flex-direction:column;align-items:center;justify-content:center;}
        p,.nav {text-align:center;line-height: 2}
        h1{color:#333;font-size:36px;margin-bottom:20px}
        .logo{width:150px;height:150px;margin-bottom:30px;background-image:url(/assets/images/sharky.svg);
            background-size:cover;background-position:center;border-radius:50%;box-shadow:0 0 10px rgba(0,0,0,.2)}
        a.link{color:#666;font-size:18px;text-align:center;margin:0 10px}
        pre{word-wrap:break-word;white-space:pre-wrap;text-align:left;
            margin: 1rem 2rem;font-size: 14px;line-height: 1.5;min-height: 200px;}
        .content{width: 100%;max-width:500px;}
        .content p{margin:1rem 2rem;}
        .version {width: 90%; border-top: 1px solid #aaa;font-size: 14px;}
        .mfa-img {width: 120px;hheight: 120px;}
        #mfa-div {display: none;}
    </style>
</head>

<body>
    <div class="logo"></div>
    <h1>{{ title }}</h1>
    <h2>欢迎使用SharkyPHP</h2>
    <div class="content">
        <div class="nav">
            <a class="link" href="/">首页</a>
            <a class="link" href="/view">视图</a>
            <a class="link" href="/database">数据库</a>
            <a class="link" href="/auth">中间件</a>
            <a class="link" href="/extension">扩展功能</a>
            <a class="link" href="https://gitee.com/bytesharky/SharkyPHP" target="_blank">Gitee</a>
            <a class="link" href="https://github.com/bytesharky/SharkyPHP" target="_blank">Github</a>
        </div>
        <pre>
            {{ content }}
            {% if $imgurl??false %}
            <p style="text-align: center;">使用以下APP扫码测试<br>Google Authenticator<br>Microsoft Authenticator</p>
            <p id="mfa-div">当前的动态密码是：<span id="mfa-code" style="color: red;"></span>，剩余时间：<span id="timer"></p>
            <p><img class="mfa-img" src="{{ imgurl }}" /></p>
            {% endif %}
        </pre>
        {% if $html??false %}
        <p><a class="link" href="/child">模板继承演示</a></p>
        {% endif %}
        {% if $imgurl??false %}
        <script>
            function countdown() {
                var timer = document.getElementById("timer");
                var interval = setInterval(() => {
                    var rest = parseInt(timer.innerText);
                    if (rest > 1) {
                        rest--;
                        timer.innerText = rest;
                    } else {
                        clearInterval(interval);
                        refresh();
                    }
                }, 1000);
            }

            function refresh() {
                var timer = document.getElementById("timer");
                var mfaCode = document.getElementById("mfa-code");
                var mfaDiv = document.getElementById("mfa-div");
                const timestamp = new Date().getTime();
                const url = `/getmfa?t=${timestamp}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.msg === 'success') {
                            mfaDiv.style.display = 'block';
                            timer.innerText = data.data.rest;
                            mfaCode.innerText = data.data.token;
                            countdown();
                        } else {
                            console.error('获取动态密码失败');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                return;
            }
            refresh();
        </script>
        {% endif %}
    </div>
    <hr>
    <div class="version">
        <p>{{ PROJECT }} Version {{ VERSION }}</p>
        <p>{{ COPYRIGHT }}</p>
    </div>
</body>

</html>
