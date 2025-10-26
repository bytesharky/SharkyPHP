<?php

/**
 * @description 简单容器模块
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Closure;
use Exception;
use Sharky\Exceptions\AbstractFailedException;
use Sharky\Exceptions\AbstractNotFoundException;

/**
 * @template T
 */
class Container
{
    // 保持一个实例
    private static $instance;

    // 缓存反射
    private $reflectionMap;

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
    public static function getInstance(): Container
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 绑定抽象类型和具体实现
    public function bind($abstract, $concrete = '', $parameters = []): void
    {
        if ($concrete == '') {
            $concrete = $abstract;
        }

        if (isset($this->bindings[$abstract])) {
            throw new InvalidArgumentException("绑定抽象类型{$abstract}冲突");
        }

        if (!is_array($parameters)) {
            throw new InvalidArgumentException("绑定抽象类型{$abstract}时遇到错误，\$parameters 必须是一个数组");
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
                throw new AbstractFailedException("无法抽象{$concrete}类");
            }
        } else if (!($concrete instanceof Closure)) {
            throw new InvalidArgumentException("抽象类型{$abstract}实现必须是一个类名或者是一个闭包函数");
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'className' => $className ?? "",
            'isClosure' => $concrete instanceof Closure,
            'parameters' => $parameters,
        ];
    }

    // 自动检测构造函数参数
    private function autoDetectParameters($className): array
    {
        $parameters = [];
        $reflector = $this->getReflectionClass($className);
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
    /**
     * @param string $abstract
     * @param array $parameters
     * @return T
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $Abstract = $abstract;
        if (!isset($this->bindings[$abstract])) {
            $classInfo = $this->getClassInfo($abstract);
            if ($classInfo['exists']) {
                $abstract = $classInfo['abstract'];
                $concrete = $classInfo['fullName'];
                if (!isset($this->bindings[$abstract])) {
                    $this->bind($abstract, $concrete);
                }
            } else {
                throw new AbstractNotFoundException("未绑定的抽象类型: {$Abstract}");
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
            $reflector = $this->getReflectionClass($concrete);
            // 获取构造函数信息
            $constructor = $reflector->getConstructor();
            if ($constructor === null) {
                // 如果类没有构造函数，直接创建实例
                $instance = $reflector->newInstance();
            } else {
                // 获取构造函数的参数信息
                $parametersToPass = [];
                foreach ($constructor->getParameters() as $parameter) {
                    $parameterName = $parameter->getName();

                    if (isset($parameters[$parameterName])) {
                        // 已经指定参数
                        $parametersToPass[$parameterName] = $parameters[$parameterName];

                    } else {
                        // 没有指定参数，首先尝试自动绑定
                        $parameterType = $parameter->getType();
                        if ($parameterType !== null) {
                            if ($parameterType instanceof ReflectionNamedType && !$parameterType->isBuiltin())
                                $parameterTypeName = $parameterType->getName();
                            $reflection = $this->getReflectionClass($parameterTypeName ?? $parameterType);
                            $namespaceName = $reflection->getNamespaceName();
                            // 只尝试绑定框架和应用命名空间下的实例
                            if (
                                str_starts_with($namespaceName, 'App\\') ||
                                str_starts_with($namespaceName, 'Sharky\\')
                            ) {
                                try {
                                    // 在容器中反向查找先前的绑定
                                    $abstractForTypeName = $this->reverseBind($parameterTypeName);
                                    $parametersToPass[$parameterName] = $this->make($abstractForTypeName);
                                } catch (Exception) {
                                    // 直接尝试创建新的实例
                                    try {
                                        $parametersToPass[$parameterName] = $this->make($parameterTypeName);
                                    } catch (Exception) {
                                        unset($parametersToPass[$parameterName]);
                                    }
                                }
                            }
                            if (empty($parametersToPass[$parameterName])) {
                                // 如果反射没有绑定成功
                                if ($parameter->isDefaultValueAvailable()) {

                                    // 绑定失败尝试，尝试获取参数的默认值
                                    $parametersToPass[$parameterName] = $parameter->getDefaultValue();

                                } else if ($parameter->allowsNull()) {
                                    // 绑定为 Null
                                    $parametersToPass[$parameterName] = null;

                                } else {
                                    // 非框架和应用内定义的类不反射
                                    // 并且没有默认值，并且不能是Null
                                    throw new InvalidArgumentException("实例化抽象类型{$Abstract}时遇到错误，缺少参数: " . $parameter->getName());
                                }
                            }
                        } else {

                            throw new InvalidArgumentException("实例化抽象类型{$Abstract}时遇到错误，缺少参数: " . $parameter->getName());
                        }
                    }
                }

                // 使用构造函数创建实例并传递参数
                $instance = $reflector->newInstanceArgs($parametersToPass);
            }
        }

        $this->instances[$abstract] = $instance;
        return $instance;
    }

    public function get($abstract, array $parameters = []): mixed
    {
        return $this->make($abstract, $parameters);
    }
    // 根据实际类名查找对应的抽象类名
    private function findAbstractForTypeName($typeName, $possible = false): mixed
    {
        if (
            is_string($possible) && isset($this->bindings[$possible]) &&
            $this->bindings[$possible]['className'] === $typeName
        ) {
            return $possible;
        }

        foreach ($this->bindings as $abstract => $binding) {

            if ($binding['isClosure'] && $binding['className'] === "") {
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

        throw new AbstractNotFoundException("未找到与实际类名 {$typeName} 对应的抽象类名");
    }

    private function reverseBind($typeName)
    {
        try {
            $classInfo = $this->getClassInfo($typeName);
            $abstract = $this->findAbstractForTypeName($typeName, $classInfo['abstract']);
            return $abstract;
        } catch (Exception $e) {

            $abstract = $classInfo['abstract'];
            $concrete = $classInfo['fullName'];
            if ($classInfo["exists"]) {
                $this->bind($abstract, $concrete);
                return $abstract;
            } else {
                throw new AbstractNotFoundException("未找到与实际类名 {$typeName} 对应的抽象类名");
            }
        }
    }

    private function getClassInfo($className): array
    {
        if (strpos($className, '\\')) {
            // 指定命名空间
            $paths = explode("\\", $className);
            $className_ = array_pop($paths);
            $classInfo = [
                "fullName" => $className,
                "className" => $className_,
                "nameSpace" => implode("\\", $paths),
                "abstract" => strtolower(md5($className)),
            ];
        } else {
            // 默认命名空间
            $fullName = __NAMESPACE__ . "\\" . ucwords($className);
            $classInfo = [
                "fullName" => $fullName,
                "className" => ucwords($className),
                "nameSpace" => __NAMESPACE__,
                "abstract" => strtolower(md5($fullName)),
            ];
        }
        $classInfo['exists'] = class_exists($classInfo['fullName']);
        return $classInfo;
    }

    private function getReflectionClass(string|object $objectOrClass): ReflectionClass
    {
        $className = is_object($objectOrClass)
            ? get_class($objectOrClass)
            : ltrim($objectOrClass, '\\'); // 去除前导反斜杠，避免重复缓存键

        $key = md5($className);

        if (!isset($this->reflectionMap[$key])) {
            $this->reflectionMap[$key] = new ReflectionClass($className);
        }

        return $this->reflectionMap[$key];
    }

}
