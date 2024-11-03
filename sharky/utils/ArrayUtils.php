<?php

/**
 * @description 多维数组工具
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Utils;

class ArrayUtils
{
    /**
     * 深度合并多个数组
     *
     * @param array ...$arrays 要合并的数组列表
     * @return array 合并后的数组
     */
    public static function deepMerge(...$arrays): array
    {
        $result = array();
        foreach ($arrays as $array) {
            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::deepMerge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * 检查数组是否为关联数组
     *
     * @param array $array 要检查的数组
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 获取数组指定路径的值
     *
     * @param array $array 数组
     * @param string|array $path 路径，可以是点号分隔的字符串或数组
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(array $array, $path, $default = null)
    {
        // 如果路径是字符串，将其转换为数组
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        // 遍历路径
        foreach ($path as $key) {
            if (!is_array($array) || !array_key_exists($key, $array)) {
                return $default;
            }
            $array = $array[$key];
        }

        return $array;
    }

    /**
     * 设置数组指定路径的值
     *
     * @param array &$array 要修改的数组
     * @param string|array $path 路径
     * @param mixed $value 要设置的值
     */
    public static function set(array &$array, $path, $value): void
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $current = &$array;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }
}
