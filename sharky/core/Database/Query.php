<?php

/**
 * @description 查询模块
 * @author Sharky
 * @date 2025-6-7
 * @version 1.5.0
 */

namespace Sharky\Core\Database;

use Sharky\Core\Collection;
use Sharky\Core\Config;
use Sharky\Core\Container;
use InvalidArgumentException;
use Sharky\Core\Model;
use Sharky\Exceptions\ConfigNotFoundException;
use Sharky\Exceptions\InvalidSqlException;


class Query
{
    protected array|null $config;
    protected $database = 'default';
    protected $tableName = '';
    protected Builder $builder;

    public function __construct(string $tableName = null, string $database = null)
    {
        /** @var Container<Config> $Container */
        $this->config = Container::getInstance()
            ->make('config')
            ->get("database." . (empty($database) ? $this->database : $database));

        if (empty($this->config)) {
            throw new ConfigNotFoundException("未找到数据库配置");
        }

        if (empty($tableName)) {
            throw new InvalidArgumentException("tableName 不能为空");
        }
        $this->tableName = $tableName;
        $this->builder = new Builder($this->tableName, $this->database);
    }

    public function alias($alias): Query
    {
        $this->builder->alias($alias);
        return $this;
    }

    public function orderBy($orderBy): Query
    {
        $this->builder->orderBy($orderBy);
        return $this;
    }

    public function groupBy($columns): Query
    {
        $this->builder->groupBy($columns);
        return $this;
    }

    public function where($conditions, $operator = 'And'): Query
    {
        $this->builder->where($conditions, $operator);
        return $this;
    }

    public function whereOr($conditions, $operator = 'And'): Query
    {
        $this->builder->whereOr($conditions, $operator);
        return $this;
    }

    public function find($value = null, $field = null): bool|Query
    {
        return $this->first($value, $field);
    }

    public function first($value = null, $field = null): bool|Model
    {
        if ($result = $this->builder->first($value, $field)->query()) {
            if (isset($result[0])) {
                return (new Model($this->tableName))->fill($result[0]);
            }
        }
        return false;
    }

    public function fields($fields): Query
    {
        $this->builder->fields($fields);
        return $this;
    }

    public function hidden($fields): Query
    {
        $this->builder->hidden($fields);
        return $this;
    }

    public function fillable($fields): Query
    {
        $this->builder->fillable($fields);
        return $this;
    }

    public function lastinsertId(): bool|int|string
    {
        return $this->builder->lastinsertId();
    }

    public function select($fields = null): Collection
    {
        $results = $this->builder->select($fields)->query();
        $records = new Collection();
        $baseModel = new Model($this->tableName);
        foreach ($results as $result) {
            $model = clone $baseModel;
            $model->fill($result);
            $records->add($model);
        }
        return $records;
    }

    public function insert($data): int
    {
        return $this->builder->insert($data)->execute();
    }

    public function update($data): int
    {
        return $this->builder->update($data)->execute();
    }

    public function delete(): int
    {
        return $this->builder->delete()->execute();
    }

    public function save($attributes, $data): int
    {
        // 处理 Model 对象, 单条更新
        if ($data instanceof Model)
            $data = $data->toArray();

        // 过滤无效字段
        $builder = $this->builder;
        $data = $builder->fillableFields($data);
        if (empty($data)) {
            throw new InvalidArgumentException("没有可更新的字段");
        }

        // 根据主键生成条件
        $primarys = $builder->getPrimarys();
        $fields = $builder->getFields();
        $wheres = [];
        if (!empty($this->attributes)) {
            foreach ($primarys as $primary) {
                if (in_array($primary, $fields) && isset($attributes[$primary])) {
                    $wheres[$primary] = $attributes[$primary];
                } else {
                    throw new InvalidSqlException("无法更新数据，缺少主键 '{$primary}'");
                }
            }
        }

        if (!empty($wheres)) {
            return $this->where($wheres)->update($data);
        } else {
            return $this->insert($data);
        }
    }

    public function getLastSql($build = false): object|null
    {
        return $this->builder->getLastSql($build);
    }

    public function limit($limit = 20, $offset = 0): Query
    {
        $this->builder->limit($limit, $offset);
        return $this;
    }

    public function paginate($page = 1, $pageSize = 20): array
    {

        // 这里必须克隆，否则查询后重置
        // 再次查询数据时，于count条件不匹配
        $clonedQuery = clone $this->builder;
        $total = $clonedQuery->count()->query();
        $total = isset($total[0]['total']) ? intval($total[0]['total']) : 0;

        $clonedQuery = clone $this->builder;
        $pageData = $clonedQuery->paginate($page, $pageSize)->query();

        $pageInfo = [
            'total' => $total,
            'data' => $pageData
        ];
        $total_pages = ceil($total / $pageSize);
        $pageInfo = array_merge($pageInfo, [
            'current_page' => min($page, $total_pages),
            'page_size' => $pageSize,
            'total_pages' => $total_pages,
            'has_more' => ($page * $pageSize) < $total
        ]);
        return $pageInfo;
    }

    public function count(): int
    {
        $result = $this->builder->count()->query();
        return isset($result[0]['total']) ? intval($result[0]['total']) : 0;
    }

    public function join($table, $on, $type = 'INNER'): Query
    {
        $this->builder->join($table, $on, $type);
        return $this;
    }

    public function leftJoin($table, $on): Query
    {
        return $this->join($table, $on, 'LEFT');
    }

    public function rightJoin($table, $on): Query
    {
        return $this->join($table, $on, 'RIGHT');
    }

    public function innerJoin($table, $on): Query
    {
        return $this->join($table, $on, 'INNER');
    }

    public function crossJoin($table, $on): Query
    {
        return $this->join($table, $on, 'CROSS');
    }

    public function forUpdate(): Query
    {
        $this->builder->forUpdate();
        return $this;
    }

    /* 魔术方法 */
    public function __isset($name)
    {
        return isset($this->builder->$name);
    }

    public function __get($name)
    {
        return $this->builder->$name;
    }

    public function __unset($name)
    {
        throw new \Exception("属性是只读的");
    }

    public function __set($name, $value): never
    {
        throw new \Exception("属性是只读的");
    }

    public function __debugInfo(): array
    {
        $query = $this->builder->__debugInfo();
        return $query;
    }
}
