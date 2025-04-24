<?php

/**
 * @description 控制器类
 * @author Sharky
 * @date 2025-4-23
 * @version 1.3.0
 */

namespace App\Controllers;

use Sharky\Libs\MFA;


class HomeController extends Controller
{
    public function index()
    {

        $content = "SharkyPHP 是一款独具特色的超迷你 MVC 框架。\n\n" .
            "虽然在中大型项目开发中，SharkyPHP 可能不是最佳选择，但如果你热衷" .
            "于探索 MVC 框架的实现原理，或者投身于小微型项目的开发，那么 SharkyPHP" .
            "或许是一个理想的伴侣。\n\n" .
            "当你觉得引入一个完整的 MVC 框架显得臃肿不堪时，不妨试试 SharkyPHP，它或许" .
            "能为你带来轻松愉快的开发体验。";
        $this->display('home/index.php', ['title' => 'Index', 'content' => $content]);
    }

    public function view()
    {

        $content = "本模板引擎实现了加载模板、处理模板中的变量、指令，并进行渲染输出。\n\n" .
            "主要特性包括支持多语言翻译、模板编译缓存以及多种常见的模板指令处理，" .
            "如变量输出、继承、块定义、条件判断和循环等。\n\n" .
            "当然我更建议使用成熟的第三方模板引擎，比如Twig，可以使用composer来安装和移除它们。\n\n" .
            "如果做API开发，则可以完全移除模版引擎，使用JSON。\n\n" .
            "直接删除sharky\\libs\\Template.php可移除此模板引擎。";

        $this->display('home/index.php', ['title' => 'View', 'content' => $content, 'html' => true]);
    }

    public function about()
    {

        $content = "SharkyPHP 是一款独具特色的超迷你 MVC 框架。\n\n" .
            "创建它既是为了让更多的人了解MVC框架的实现原理。\n\n" .
            "也是想要构建一个可用于小微型项目的超轻量框架。\n\n" .
            "任何人都可以复制、修改、分发和使用它的副本" .
            "但是由此产生的一切风险和法律责任均与本人无关。";
        $this->display('home/index.php', ['title' => 'About', 'content' => $content]);
    }

    public function database()
    {

        $content = "数据模型已实现基础的增删改查、分页查询和事务机制。\n\n" .
            "以及简单多表联合查询，如果你的SQL查询相对比较复杂\n\n" .
            "可以用 Database 类的 query 和 execute 方法实现。\n\n" .
            "当然你有更好的联合查询实现方式，也可以提出宝贵的建议，\n\n". 
            "我们会充分考虑您的建议，并十分感谢。\n\n";
        $this->display('home/index.php', ['title' => 'Database', 'content' => $content]);
    }
    
    public function auth()
    {
        $content = "路由中间件是处理请求和响应的过滤层。\n\n" .
            "在路由执行前后介入，可用于身份验证、日志记录等。\n\n" .
            "它能对请求数据预处理，也能在响应返回前修改。\n\n" .
            "如验证用户登录，只有通过验证才进入路由逻辑，提升应用安全性与可维护性。\n\n";
        $this->display('home/index.php', ['title' => 'Auth', 'content' => $content]);
    }

    public function child()
    {

        $content = "这是模板继承的测试，它继承了base.html.";
        $this->display('home/child.php', ['title' => '模板继承的测试', 'content' => $content]);
    }

    public function extension()
    {
        $mfa = new MFA('JBSWY3DPEHPK3PXP');

        $content = "目前我添加了两个扩展组件\n\n" .
            "JWT：JSON Web Token，方便实现API 认证、单点登录等场景\n\n" .
            "MFA：Multi-Factor Authentication，方便实现两步身份验证\n\n";
        
        $imgurl = 'https://www.doffish.com/QRCode.do?text=' . urlencode($mfa->getQRCodeUrl('MFA 测试'));

        $this->display('home/index.php', ['title' => 'Extension', 'content' => $content, 'imgurl' => $imgurl]);
    }

    public function getmfa()
    {
        $mfa = new MFA('JBSWY3DPEHPK3PXP');
        $tokens = $mfa->getTOTPToken();
        $token = $tokens['token'][1];
        $rest = $tokens['rest'];

        return json_encode([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'token' => $token,
                'rest' => $rest
            ]
        ]);
    }
}