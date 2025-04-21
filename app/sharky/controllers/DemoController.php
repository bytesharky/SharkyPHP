<?php

/**
 * @description 控制器类
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace App\Controllers;

class DemoController extends Controller
{
    // 模拟文章列表数据
    protected $article_list = [
        [
            'title' => '这是一个内置模版引擎渲染的页面',
            'url' => '/demo/0',
            'author' => 'Sharky',
            'published_date' => '2024-11-02',
            'excerpt' => 'SharkyPHP 是一款独具特色的超迷你 MVC 框架',
            'tags' => ['SharkyPHP', 'MVC', 'PHP']
        ],
        [
            'title' => '如何安装Twig',
            'url' => '/demo/1',
            'author' => 'Sharky',
            'published_date' => '2024-11-02',
            'excerpt' => '使用composer require twig/twig命令',
            'tags' => ['composer', 'twig', 'PHP']
        ],
        [
            'title' => '如何卸载Twig',
            'url' => '/demo/2',
            'author' => 'Sharky',
            'published_date' => '2024-11-02',
            'excerpt' => '使用composer remove twig/twig命令',
            'tags' => ['composer', 'twig', 'PHP']
        ]
    ];

    public function list()
    {
        $this->display('demo/index.php', [
            'article_list' => $this->article_list,
            'COPYRIGHT' => COPYRIGHT,
            'PROJECT' => PROJECT,
        ]);
    }

    public function show($id)
    {

        echo ('<pre>');
        if (isset($this->article_list[$id])) {
            var_dump($this->article_list[$id]);
        } else {
            var_dump('数据不存在');
        }
        echo ('</pre>');
        echo ('<a href="/demo/list">(返回列表)</a>');
    }
}
