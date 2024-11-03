<?php

/**
 * @description 简单容器模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

use ReflectionClass;
use Closure;
use Exception;

class Container
{
    // 保持一个实例
    private static $instance;
    private function __construct()
    {
        // 私有构造函数
        // 禁止外部实例化
    }

    // 用于存储绑定关系的数组，键为抽象类型名，值为对应的具体实现（可以是闭包函数或者类名等）
    private $bindings = [];
// 用于存储已经创建的实例，键为抽象类型名，值为对应的实例对象
    private $instances = [];

    // 获取容器实例
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 绑定抽象类型和具体实现
    public function bind($abstract, $concrete = '', $parameters = [])
    {
        if ($concrete == '') {
            $concrete = $abstract;
        }

        if (!($concrete instanceof Closure)) {
// 驼峰命名
            $concrete = ucwords($concrete);
// 如果不是完整类名添加当前命名空间
            if (strpos($concrete, '\\') === false) {
                $concrete = __NAMESPACE__ . '\\' . $concrete;
            }
            $className = $concrete;
        } else {
            $instance = $concrete($this, $parameters);
            $className = get_class($instance);
            $instance = null;
        }

        $this->bindings[$abstract] = ['concrete' => $concrete, 'className' => $className , 'parameters' => $parameters];
    }

    // 创建类的实例，解析并注入依赖
    public function make($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            throw new Exception("未绑定抽象类型: {$abstract}");
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];
        $parameters = $binding['parameters'];
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if ($concrete instanceof Closure) {
            $instance = $concrete($this, $parameters);
        } else {
        // 使用反射创建实例
            $reflector = new ReflectionClass($concrete);
        // 获取构造函数信息
            $constructor = $reflector->getConstructor();
            if ($constructor === null) {
        // 如果类没有构造函数，直接创建实例
                $instance = $reflector->newInstance();
            } else {
    // 获取构造函数的参数信息
                $parametersToPass = [];
                foreach ($constructor->getParameters() as $parameter) {
                    $parameterType = $parameter->getType();
                    if ($parameterType === null) {
                    // 如果参数没有指定类型
                        // 直接使用绑定的参数值
                        if (isset($parameters[$parameter->getName()])) {
                            $parametersToPass[] = $parameters[$parameter->getName()];
                        } else {
                        // 如果没有指定参数值，抛出异常
                            throw new \Exception("缺少参数: " . $parameter->getName());
                        }
                    } else {
            // 如果参数指定了类型，通过容器获取对应的实例
                        $parameterTypeName = $parameterType->getName();
            // 这里查找抽象类名对应的绑定关系
                        $abstractForTypeName = $this->findAbstractForTypeName($parameterTypeName);
                        $parametersToPass[] = $this->make($abstractForTypeName);
                    }
                }

            // 使用构造函数创建实例并传递参数
                $instance = $reflector->newInstanceArgs($parametersToPass);
            }
        }

        $this->instances[$abstract] = $instance;
        return $instance;
    }

    // 解析具体实现，如果是闭包则执行闭包并传入容器本身
    private function resolve($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        return $concrete;
    }

    // 根据实际类名查找对应的抽象类名
    private function findAbstractForTypeName($typeName)
    {

        foreach ($this->bindings as $abstract => $binding) {
            $className = $binding['className'];
            if ($className === $typeName) {
                return $abstract;
            }
        }

        throw new Exception("未找到与实际类名 {$typeName} 对应的抽象类名");
    }
}
