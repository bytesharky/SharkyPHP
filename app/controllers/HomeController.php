<?php

/**
 * @description 控制器类
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace App\Controllers;

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
        $this->display('home/index.php', ['page' => 'Index', 'content' => $content]);
    }

    public function view()
    {

        $content = "此模板引擎仅作为演示学习，实现方式非常非常的简单\n\n" .
        "所以这里并不建议直接使用它，建议使用成熟的模板引擎，比如Twig，" .
        "可以使用composer来安装和移除它们。\n\n" .
        "直接删除sharky\\libs\\Template.php可移除此模板引擎。\n\n" .
        "如果做API开发，则可以完全移除模版引擎，使用JSON。";
        $html = '<p><a class="link" href="/demo/list">查看 Twig Demo</a></p>';
        $this->display('home/index.php', ['page' => 'View', 'content' => $content, 'html' => $html]);
    }

    public function about()
    {

        $content = "SharkyPHP 是一款独具特色的超迷你 MVC 框架。\n\n" .
        "创建它既是为了让更多的人了解MVC框架的实现原理。\n\n" .
        "也是想要构建一个可用于小微型项目的超轻量框架。\n\n" .
        "任何人都可以复制、修改、分发和使用它的副本" .
        "但是由此产生的一切风险和法律责任均与本人无关。";
        $this->display('home/index.php', ['page' => 'About', 'content' => $content]);
    }

    public function database()
    {

        $content = "数据模型已实现基础的增删改查、分页查询和事务机制。\n\n" .
        "但复杂的多表联合查询尚未实现，若确实有需要\n\n" .
        "可暂用 Database 类的 query 和 execute 方法过渡。\n\n" .
        "当然也可在该模型基础上自行实现，若能共享代码，我们将十分感谢。\n\n";
        $this->display('home/index.php', ['page' => 'Database', 'content' => $content]);
    }
}
