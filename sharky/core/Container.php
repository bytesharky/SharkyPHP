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

        if (!is_array($parameters)) {
            throw new Exception('参数必须是一个数组');
        }
        // 如果第二个参数传入的是数组，且第三个参数是空
        // 那么就视为第二个参数是参数
        if (is_array($concrete) && !empty($concrete)) {
            $parameters = $concrete;
            $concrete = $abstract;
        }

        if (is_string($concrete)) {
            $classInfo = $this->getClassInfo($concrete);
            if ($classInfo['exists']) {
                $className = $classInfo['fullName'];
                // 自动分析构造函数参数并预设依赖
                $autoParameters = $this->autoDetectParameters($className);
                $parameters = array_merge($autoParameters, $parameters);
            } else {
                throw new Exception("无法抽象{$concrete}类");
            }
        } else if (!($concrete instanceof Closure)) {
            throw new Exception('实现必须是一个类名或者是一个闭包函数');
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'className' => $className??"",
            'isClosure' => $concrete instanceof Closure,
            'parameters' => $parameters,
        ];
    }

    // 自动检测构造函数参数
    private function autoDetectParameters($className)
    {
        $parameters = [];
        $reflector = new ReflectionClass($className);
        $constructor = $reflector->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                // 如果参数没有类型提示且没有默认值，则设为空
                if (!$parameter->getType() && !$parameter->isDefaultValueAvailable()) {
                    $parameters[$parameter->getName()] = null;
                }
            }
        }

        return $parameters;
    }

    // 创建类的实例，解析并注入依赖
    public function make($abstract, array $parameters = [])
    {
        if (!isset($this->bindings[$abstract])) {
            $classInfo = $this->getClassInfo($abstract);
            if ($classInfo['exists']) {
                $abstract = $classInfo['abstract'];
                $concrete = $classInfo['fullName'];
                $this->bind($abstract, $concrete);
            } else {
                throw new Exception("未绑定的抽象类型: {$abstract}");
            }
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];
        $parameters = array_merge($binding['parameters'], $parameters);
        
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if ($concrete instanceof Closure) {
            $instance = $concrete($this, $parameters);
            $this->bindings[$abstract]['className'] = get_class($instance);
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
                            throw new Exception("缺少参数: " . $parameter->getName());
                        }
                    } else {
                        // 如果参数指定了类型，通过容器获取对应的实例
                        $parameterTypeName = $parameterType->getName();
                        // 这里查找抽象类名对应的绑定关系
                        $abstractForTypeName = $this->reverseBind($parameterTypeName);
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

    public function get($abstract, array $parameters = []){
        return $this->make($abstract, $parameters);
    }
    // 根据实际类名查找对应的抽象类名
    private function findAbstractForTypeName($typeName, $possible = false)
    {
        if (is_string($possible) && isset($this->bindings[$possible]) &&
        $this->bindings[$possible]['className'] === $typeName ){
            return $possible;
        }

        foreach ($this->bindings as $abstract => $binding) {

            if ($binding['isClosure'] && $binding['className'] === "" ){
                $concrete = $binding['concrete'];
                $parameters = $binding['parameters'];
                $instance = $concrete($this, $parameters);
                $this->instances[$abstract] = $instance;
                $binding['className'] = get_class($instance);
            }

            if ($binding['className'] === $typeName) {
                return $abstract;
            }
        }

        throw new Exception("未找到与实际类名 {$typeName} 对应的抽象类名");
    }
    
    private function reverseBind($typeName){
        try {
            $classInfo = $this->getClassInfo($typeName);
            $abstract = $this->findAbstractForTypeName($typeName,$classInfo['abstract']);
            return $abstract;
        }catch (Exception $e){

            $abstract = $classInfo['abstract'];
            $concrete = $classInfo['fullName'];
            if ($classInfo["exists"]){
                $this->bind($abstract, $concrete);
                return $abstract;
            }else{
                throw new Exception("未找到与实际类名 {$typeName} 对应的抽象类名");
            }
        }
    }

    private function getClassInfo($className){
        if (strpos($className, '\\')){
            // 指定命名空间
            $paths = explode("\\", $className);
            $className_ = array_pop($paths);
            $classInfo = [
                "fullName" => $className,
                "className" => $className_,
                "nameSpace" => implode("\\",$paths),
                "abstract" => strtolower($className_),
            ];
        } else {
            // 默认命名空间
            $classInfo = [
                "fullName" => __NAMESPACE__."\\{$className}",
                "className" => ucwords($className),
                "nameSpace" => __NAMESPACE__,
                "abstract" => strtolower($className),
            ];
        }
        $classInfo['exists'] = class_exists($classInfo['fullName']);
        return $classInfo;
    }
}
