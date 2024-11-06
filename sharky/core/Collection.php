<?php

/**
 * @description 数据集合模块
 * @author Sharky
 * @date 2024-11-5
 * @version 1.0.0
 */


namespace Sharky\Core;

use \Iterator;

class Collection implements Iterator
{
    protected $items = [];
    private $position = 0;
    
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    // 将集合转换为数组
    public function toArray()
    {
        return array_map(function ($item) {
            return $item instanceof Model ? $item->toArray() : $item;
        }, $this->items);
    }

    // 应用回调函数到每个元素并返回新集合
    public function map(callable $callback)
    {
        $mapped = array_map($callback, $this->items);
        return new static($mapped);
    }

    // 添加单个模型
    public function add($item)
    {
        $this->items[] = $item;
    }

    // 获取所有项
    public function all()
    {
        return $this->items;
    }

    // 返回集合的数量
    public function count(){
        return count($this->items);
    }

    // 添加count属性的访问器方法（getter）
    public function __get($name) {
        if ($name === 'count') {
            return count($this->items);
        }
        return null;
    }

    public function __debugInfo() {
        return [
            "count" => count($this->items),
        ];
    }

    // 返回当前元素
    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    // 返回当前元素的键
    public function key(): int
    {
        return $this->position;
    }

    // 将内部指针向前移动一位
    public function next(): void
    {
        $this->position++;
    }

    // 重置内部指针到第一个元素
    public function rewind(): void
    {
        $this->position = 0;
    }

    // 检查当前位置是否有效
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }
}
