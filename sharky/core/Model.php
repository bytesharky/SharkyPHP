<?php

/**
 * @description 数据模型模块
 * @author Sharky
 * @date 2025-6-7
 * @version 1.5.0
 */

namespace Sharky\Core;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use Sharky\Core\Database\Query;
use Sharky\Exceptions\ConfigNotFoundException;

/**
 * @method static getInstance(): Model
 * @method static alias($alias): static
 * @method static orderBy($orderBy): static
 * @method static groupBy($columns): static
 * @method static where($conditions, $operator = 'And'): Model
 * @method static whereOr($conditions, $operator = 'And'): Model
 * @method static find($value = null, $field = null): bool|Model
 * @method static first($value = null, $field = null): bool|Model
 * @method static fields($fields): static
 * @method static bool|int|string lastinsertId()
 * @method static select($fields = null): Collection
 * @method static insert($data): int
 * @method static update($data): int
 * @method static delete(): int
 * @method static getLastSql($build = false): object|null
 * @method static limit($limit = 20, $offset = 0): static
 * @method static paginate($page = 1, $pageSize = 20): array
 * @method static count(): int
 * @method static join($table, $on, $type = 'INNER'): static
 * @method static leftJoin($table, $on): Model
 * @method static rightJoin($table, $on): Model
 * @method static innerJoin($table, $on): Model
 * @method static crossJoin($table, $on): Model
 * @method static getTableWithAlias($alias = null): string 
 * @method static forUpdate(): static
 * @method static fillable($fields): static
 * @method static hidden($fields): static
 * @method toArray(): array
 * @method save($data = []): int
 * @method fill($attributes): static
 */
class Model
{
    protected Query $query;
    protected array $attributes = [];
    protected array $hidden = [];
    protected array $fillable  = [];
    protected string $database = 'default';
    protected string $tableName = '';

    public function __construct(string $tableName = null, string $database = null)
    {
        /** @var Container<Config> $Container */
        $config = Container::getInstance()
            ->make('config')
            ->get("database." . (empty($database) ? $this->database : $database));

        if (empty($config)) {
            throw new ConfigNotFoundException("未找到数据库配置");
        }

        // 如果未指定表名，使用默认名
        $tableName = !empty($tableName)
            ? $tableName
            : (!empty($this->tableName)
                ? $this->tableName
                : static::generateTableName()
            );

        if (empty($tableName)) {
            throw new InvalidArgumentException("tableName 不能为空");
        }
        $this->tableName = $tableName;
        $this->query = new Query($this->tableName, $this->database);
        $this->query->hidden($this->hidden);
        $this->query->fillable($this->fillable);
    }

    public static function getInstance(): Model
    {
        /** @var Container<Model> $Container */
        $Container = Container::getInstance();
        $Instance = $Container->make(static::class);
        return $Instance;
    }

    public function __call($method, $args)
    {
        if ($method === "save") {
            $this->__save($args);
            $args = [$this->toArray(), ...$args];
        }
        $restul = self::__callInstance($this, $method, $args);
        return $restul;
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getInstance();
        if ($method === "save") {
            $instance->__save($args);
            $args = [[], ...$args];
        }
        return static::__callInstance($instance, $method, $args);
    }

    private static function __callInstance($instance, $method, $args)
    {
        $query = $instance->query;
        if (method_exists($query, $method)) {
            $result = call_user_func_array([$query, $method], $args);
            if ($result instanceof Query){
                return $instance;

            } else if($result instanceof Model){
                return (new static())->fill($result->toArray());

            } else if ($result instanceof Collection){
                $results = $result->toArray();
                $collection = new Collection();
                $modle = new static();
                foreach ($results as $item){
                   $collection->add((clone $modle)->fill($item));
                }
                return $collection;
            }
            return $result;
        } else {
            throw new BadMethodCallException("Call Not Found Method `{$method}`");
        }
    }

    // 填充模型
    public function fill($attributes): static
    {
        if (is_array($attributes)) {
            $this->attributes = $attributes;
        }
        return $this;
    }

    // 转为数组
    public function toArray(): array
    {
        return $this->attributes;
    }


    // 修改模型数据
    private function __save($data = []): void
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    private static function generateTableName(): string
    {
        $className = static::class;
        $className = substr($className, strrpos($className, '\\') + 1);
        $tableName = str_replace('Model', '', $className);
        $pattern = '/(?<!^)[A-Z]/';
        $tableName = preg_replace($pattern, '_$0', $tableName);
        return strtolower($tableName);
    }

    /* 魔术方法 */
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __unset($name): void
    {
        unset($this->attributes[$name]);
    }

    public function __debugInfo(): array
    {
        $query = $this->query;
        return [
            "query" => $query,
            "attributes" => $this->attributes,
        ];

    }
}
