<?php

/**
 * @description 查询构造模块
 * @author Sharky
 * @date 2025-6-7
 * @version 1.5.0
 */

namespace Sharky\Core\Database;

use Closure;
use InvalidArgumentException;

use Sharky\Core\Config;
use Sharky\Core\Container;
use Sharky\Core\Database;
use Sharky\Core\Database\Pool;
use Sharky\Core\Model;
use Sharky\Exceptions\InvalidSqlException;
use Sharky\Exceptions\ConfigNotFoundException;
use Sharky\Exceptions\InsecurityExecuteException;
use Throwable;

class Builder
{
    private array|null $config;
    private string $tableName;
    private array $where = [];
    private array $groupByColumns = [];
    private array $orderByConditions = [];
    private array $currentGroup = [];
    private array $groups = [];
    private readonly array $primarys;
    private readonly array $fields;
    private readonly Closure $default;
    private array $fillable = [];
    private array $select = [];
    private array $hidden = [];
    private string $lastSql = '';
    private array $lastParams = [];
    private int|null $limit = null;
    private int|null $offset = null;
    private int|null $page = null;
    private int|null $pageSize = null;
    private Collection|null $records = null;
    private Pool|null $connectPool = null;
    private string|null $prefix = null;
    private string $alias = '';
    private array $join = [];
    private bool $forUpdate = false;
    private array $builder;

    public function __construct(string $tableName, string $database = "default")
    {
        /** @var Container<Config> $Container */
        $this->config = Container::getInstance()
            ->make('config')
            ->get("database.{$database}");

        if (empty($this->config)) {
            throw new ConfigNotFoundException("未找到数据库配置");
        }

        if (!isset($this->prefix)) {
            $this->prefix = $this->config['prefix'] ?? 'sharky_';
        }

        // 如果未指定表名，使用默认名
        $this->tableName = $tableName;
        $this->connectPool = Database::connects($database);
        $this->default = fn(): array => array_diff($this->fields, $this->hidden);
        $this->fields = $this->connectPool->slave()->getFields("{$this->prefix}{$this->tableName}");
        $this->primarys = $this->connectPool->slave()->getPrimarys("{$this->prefix}{$this->tableName}");
    }

    public function alias($alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    public function orderBy($columns): static
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        } else if (is_array($columns) && isset($columns[0]) && !is_array($columns[0])) {
            $columns = [$columns];
        }

        if (!isset($columns[0])) {
            $keys = array_keys($columns);
            $orderBys = array_map(
                function ($key) use ($columns) {
                    $field = $this->escapeField($key);
                    $direction = strtolower($columns[$key]);
                    if (in_array($direction, ['desc', 'asc'])) {
                        return "{$field} {$direction}";
                    }
                },
                $keys
            );
        } else {
            $orderBys = array_map(function ($item): string {

                $parts = (is_string($item))
                    ? explode(' ', preg_replace('/\s+/', ' ', $item))
                    : $item;

                if (is_array($parts)) {
                    $field = $this->escapeField($parts[0]);
                    $direction = isset($parts[1]) ? strtolower($parts[1]) : 'asc';
                    if (in_array($direction, ['desc', 'asc'])) {
                        return "{$field} {$direction}";
                    }
                }
                return '';
            }, $columns);
        }

        $this->orderByConditions = array_merge($this->orderByConditions, $orderBys);

        return $this;
    }

    public function groupBy($columns): static
    {
        if (is_string($columns)) {
            $this->groupByColumns[] = $columns;
        } elseif (is_array($columns)) {
            $this->groupByColumns = array_merge($this->groupByColumns, $columns);
        }
        return $this;
    }

    private function addWhere($conditions, $link, $operator = 'And'): static
    {
        // 是闭包函数
        if ($conditions instanceof Closure) {
            $this->beginGroup();
            call_user_func($conditions, $this);
            $this->endGroup();
            return $this;
        }

        if (!is_array($conditions)) {
            return $this;
        }

        if (empty($this->currentGroup)) {

            // 规范where
            $conditions = $this->normalizeWhere($conditions);

            // 不在分组中，直接添加到主where数组
            if (isset($conditions[0]) && !is_array($conditions[0])) {
                // 单个条件
                $this->where[] = ['condition' => $conditions, 'operator' => $link];
            } else {
                // 多个条件，自动建组
                $group = array_map(
                    fn($condition) =>
                    ['condition' => $condition, 'operator' => $operator],
                    $conditions
                );
                $groupId = uniqid('group_');
                $this->groups[$groupId] = $group;
                $this->where[] = ['type' => 'group', 'id' => $groupId, 'operator' => $link];
            }
        } else {
            // 在分组中，添加到当前分组
            if (isset($conditions[0]) && !is_array($conditions[0])) {
                $this->groups[end($this->currentGroup)][] = ['condition' => $conditions, 'operator' => $link];
            } else {
                foreach ($conditions as $condition) {
                    $this->groups[end($this->currentGroup)][] = ['condition' => $condition, 'operator' => $link];
                }
            }
        }
        return $this;
    }

    public function where($conditions, $operator = 'And'): Builder
    {
        return $this->addWhere($conditions, 'And', $operator);
    }

    public function whereOr($conditions, $operator = 'And'): Builder
    {
        return $this->addWhere($conditions, 'Or', $operator);
    }

    private function normalizeWhere($conditions): array
    {
        try {
            $normalized = [];

            // 单条件解析
            if (count($conditions) === 2 && isset($conditions[0]) && is_string($conditions[0])) {
                // 格式：['字段名', '值']
                $normalized[] = [$conditions[0], "=", $conditions[1]];

            } elseif (count($conditions) === 3 && isset($conditions[0]) && is_string($conditions[0]) && is_string($conditions[1])) {
                // 格式：['字段名', '运算符', '值']
                $normalized[] = [$conditions[0], $conditions[1], $conditions[2]];

            } else {
                // 多条件解析
                foreach ($conditions as $key => $value) {
                    if (is_string($key)) {
                        if (is_array($value) && count($value) === 2) {
                            // 格式： ['字段名' => ['运算符', '值'], ...]
                            $normalized[] = [$key, $value[0], $value[1]];
                        } else {
                            // 格式：['字段名' => '值', ...]
                            $normalized[] = [$key, '=', $value];
                        }

                    } elseif (is_array($value) && count($value) === 1 && !isset($value[0])) {
                        // 格式：[['字段名' => '值'], ...]
                        foreach ($value as $subKey => $subValue) {
                            $normalized[] = [$subKey, "=", $subValue];
                        }
                    } elseif (is_array($value) && count($value) === 2 && isset($value[0]) && is_string($value[0])) {
                        // 格式：[['字段名', '值'], ...]
                        $normalized[] = [$value[0], "=", $value[1]];

                    } elseif (is_array($value) && count($value) === 3 && isset($value[0]) && is_string($value[0]) && is_string($value[1])) {
                        // 格式：[['字段名', '运算符', '值'], ...]
                        $normalized[] = [$value[0], $value[1], $value[2]];

                    } else {
                        throw new InvalidArgumentException('where 参数错误', -1);
                    }
                }
            }
            return $normalized;
        } catch (Throwable $err) {
            throw new InvalidArgumentException('where 参数错误', -1, $err);
        }
    }

    private function buildWhere(): array
    {
        $whereSql = '';
        $params = [];
        if (!empty($this->where)) {
            $firstCondition = true;
            foreach ($this->where as $item) {
                if (!$firstCondition) {
                    $whereSql .= " {$item['operator']} ";
                }

                if (isset($item['type']) && $item['type'] === 'group') {
                    // 处理分组
                    $groupSql = $this->buildGroupConditions($this->groups[$item['id']], $params);
                    if (!empty($groupSql)) {
                        $whereSql .= "($groupSql)";
                        $firstCondition = false;
                    }
                } else {
                    // 处理普通条件
                    $condition = $item['condition'];
                    // 允许使用整数，否过滤并加反引号                    
                    $field = preg_match('/^\d+$/', $condition[0]) ? $condition[0] : $this->escapeField($condition[0]);
                    switch (strtolower($condition[1])) {
                        case 'in':
                            $condition[2] = is_array($condition[2]) ? $condition[2] : [$condition[2]];
                            $placeholders = implode(',', array_fill(0, count($condition[2]), '?'));
                            $whereSql .= "{$field} IN ({$placeholders})";
                            $params = array_merge($params, $condition[2]);
                            break;
                        default:
                            $whereSql .= "{$field} {$condition[1]} ?";
                            $params[] = $condition[2];
                            break;
                    }

                    $firstCondition = false;
                }
            }
        }
        if ($whereSql) {
            $whereSql = " WHERE {$whereSql}";
            return [$whereSql, $params];
        }
        return ['', []];
    }

    private function beginGroup($operator = 'AND'): static
    {
        $groupId = uniqid('group_');
        $this->groups[$groupId] = [];
        $this->where[] = ['type' => 'group', 'id' => $groupId, 'operator' => $operator];
        $this->currentGroup[] = $groupId;
        return $this;
    }

    private function endGroup(): static
    {
        array_pop($this->currentGroup);
        return $this;
    }

    private function buildGroupConditions($conditions, &$params): string
    {
        $whereSql = '';
        $firstCondition = true;
        foreach ($conditions as $item) {
            if (!$firstCondition) {
                $whereSql .= " {$item['operator']} ";
            }

            $condition = $item['condition'];
            // 允许使用整数，否过滤并加反引号                    
            $field = preg_match('/^\d+$/', $condition[0]) ? $condition[0] : $this->escapeField($condition[0]);
            switch (strtolower($condition[1])) {
                case 'in':
                    $condition[2] = is_array($condition[2]) ? $condition[2] : [$condition[2]];
                    $placeholders = implode(',', array_fill(0, count($condition[2]), '?'));
                    $whereSql .= "{$field} IN ({$placeholders})";
                    $params = array_merge($params, $condition[2]);
                    break;
                default:
                    $whereSql .= "{$field} {$condition[1]} ?";
                    $params[] = $condition[2];
                    break;
            }
            $firstCondition = false;
        }

        return $whereSql;
    }

    public function find($value = null, $field = null): Builder
    {
        return $this->first($value, $field);
    }

    public function first($value = null, $field = null): Builder
    {
        if ($value !== null) {
            if (empty($field)) {
                if (count($this->primarys) === 1) {
                    $field = $this->primarys[0];
                } else {
                    throw new InvalidArgumentException("主键不唯一，请明确字段或使用WHERE限定查询条件");
                }
            }
            $this->where([$field, '=', $value]);
        }
        return $this->limit(1)->select();
    }

    public function fields($fields): static
    {
        if (is_string($fields)) {
            $this->select = array_map('trim', explode(',', $fields));
        } elseif (is_array($fields)) {
            $this->select = $this->arrayMap([$this, 'myTrim'], $fields);
        }
        return $this;
    }

    public function hidden($fields): static
    {
        if (is_string($fields)) {
            $this->hidden = array_map('trim', explode(',', $fields));
        } elseif (is_array($fields)) {
            $this->hidden = $this->arrayMap([$this, 'myTrim'], $fields);
        }
        return $this;
    }

    public function fillable($fields): static
    {
        if (is_string($fields)) {
            $this->fillable = array_map('trim', explode(',', $fields));
        } elseif (is_array($fields)) {
            $this->fillable = $this->arrayMap([$this, 'myTrim'], $fields);
        }
        return $this;
    }

    public function lastinsertId(): bool|int|string
    {
        return $this->connectPool->master()->lastInsertId();
    }

    public function select($fields = null): Builder
    {

        $fields = !empty($fields) ? $fields : (!empty($this->select) ? $this->select : ($this->default)());
        $fieldsSql = $this->buildFields($fields);

        $groupBy = '';
        $orderBy = '';
        $table = $this->getTableWithAlias();

        [$joins, $joinParams] = $this->buildJoin();
        [$whereSql, $whereParams] = $this->buildWhere();
        $params = array_merge($joinParams, $whereParams);

        if (!empty($this->groupByColumns)) {
            $groupBy = " GROUP BY " . implode(', ', $this->groupByColumns);
        }
        if (!empty($this->orderByConditions)) {
            $orderBy = " ORDER BY " . implode(', ', $this->orderByConditions);
        }

        $sql = "SELECT {$fieldsSql} FROM {$table}{$joins}{$whereSql}{$groupBy}{$orderBy}";
        if ($this->forUpdate) {
            $sql .= " FOR UPDATE";
        }

        // 添加 LIMIT 和 OFFSET
        if ($this->limit !== null && $this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset > 0) {
                $sql .= " OFFSET " . $this->offset;
            }
        }

        $this->setLastSql($sql, $params);

        return $this;
    }

    public function insert($data): Builder
    {
        if ($data instanceof Builder) {
            // 处理子查询
            $subQuery = $data->getLastSql();
            if (empty($subQuery)) {
                throw new InvalidSqlException("子查询没有有效的SQL语句");
            }

            $data = " ({$subQuery->sql})";
            $params = $subQuery->params;
            $table = $this->getTableWithAlias("");

            $fieldsSql = $this->buildFields(!empty($this->fillable) ? $this->fillable : $this->fields);
            $fieldsSql = $fieldsSql ? " ({$fieldsSql})" : "";

            $sql = "INSERT INTO {$table}{$fieldsSql}{$data}";

            $this->setLastSql($sql, $params);

            return $this;

        } elseif (is_array($data)) {
            // 处理普通数组
            return $this->insertArray($data);

        } elseif ($data instanceof Model) {
            // 处理 Model 对象, 单条插入
            $insertData = $data->toArray();
            return $this->insertArray($insertData);

        } elseif ($data instanceof Collection) {
            // 处理 Collection 对象，批量插入
            $insertData = $data->toArray();
            return $this->insertArray($insertData);

        } else {
            throw new InvalidArgumentException("数据格式不正确");
        }
    }

    private function insertArray($data): Builder
    {
        // 过滤无效字段
        $data = $this->fillableFields($data);
        if (empty($data)) {
            throw new InvalidArgumentException("没有可插入的字段");
        }

        $table = $this->getTableWithAlias("");

        // 处理批量插入
        $params = [];
        if (isset($data[0]) && is_array($data[0])) {
            $fields = array_keys($data[0]);
            $values = [];
            foreach ($data as $row) {
                $placeholders = [];
                foreach ($fields as $field) {
                    $placeholders[] = '?';
                    $params[] = $row[$field];
                }
                $values[] = '(' . implode(', ', $placeholders) . ')';
            }

            $sql = "INSERT INTO {$table} (" .
                implode(', ', array_map(function ($item) {
                    return '`' . addslashes(trim($item)) . '`';
                }, $fields)) .
                ") VALUES " .
                implode(', ', $values);
            $this->setLastSql($sql, $params);
        } else {
            // 单条插入
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO {$table} (" .
                implode(', ', array_map(function ($item) {
                    return '`' . addslashes(trim($item)) . '`';
                }, $fields)) .
                ") VALUES (" .
                implode(', ', $placeholders) .
                ")";

            $this->setLastSql($sql, array_values($data));
            $params = array_values($data);
        }

        return $this;
    }

    public function update($data): Builder
    {
        // 处理 Model 对象, 单条更新
        if ($data instanceof Model)
            $data = $data->toArray();

        // 过滤无效字段
        $data = $this->fillableFields($data);
        if (empty($data)) {
            throw new InvalidArgumentException("没有可更新的字段");
        }

        [$joins, $joinParams] = $this->buildJoin();
        [$whereSql, $whereParams] = $this->buildWhere();

        $table = $this->getTableWithAlias();
        $sets = [];
        $setParams = [];
        foreach ($data as $field => $value) {

            $field = ($this->alias ? "`{$this->alias}`." : "") . '`' . addslashes(trim($field)) . '`';

            if (is_callable($value)) {
                // 处理闭包函数
                $value = call_user_func($value);
                $sets[] = "$field = $value";
            } else {
                $sets[] = "$field = ?";
                $setParams[] = $value;
            }
        }


        $params = array_merge($joinParams, $setParams, $whereParams);
        if ($whereSql === "") {
            throw new InsecurityExecuteException("为了安全起见，不允无条件更新");
        }
        $sql = "UPDATE {$table}{$joins} SET " . implode(',', $sets) . $whereSql;
        $this->setLastSql($sql, $params);

        return $this;
    }

    public function delete(): Builder
    {

        [$joins, $joinParams] = $this->buildJoin();
        [$whereSql, $whereParams] = $this->buildWhere();
        $params = array_merge($joinParams, $whereParams);

        if ($whereSql === "") {
            throw new InsecurityExecuteException("为了安全起见，不允无条件删除");
        }

        if (empty($joins)) {
            $table = $this->getTableWithAlias("");
            $sql = "DELETE FROM {$table}" . $whereSql;
        } else {
            $table = $this->getTableWithAlias();
            $sql = "DELETE {$this->alias} FROM {$table}{$joins}" . $whereSql;
        }

        $this->setLastSql($sql, $params);

        return $this;
    }

    private function myTrim(mixed $string, string $characters = " \n\r\t\v\x00"): mixed
    {
        return is_string($string)
            ? trim($string, $characters)
            : $string;
    }

    private function arrayMap($callback, $array): array
    {
        return array_map(
            fn($item) =>
            is_array($item)
            ? $this->arrayMap($callback, $item)
            : $callback($item),
            $array
        );
    }

    private function buildFields($fields): string
    {
        if ((is_array($fields) && in_array("*", $fields)) || $fields === '*') {
            return '*';
        }

        $sqlFields = [];
        $fields = is_array($fields) ? $fields : explode(',', $fields);
        foreach ($fields as $field) {
            if (is_array($field)) {
                // 处理带别名的字段 ['field', 'alias']
                if (count($field) !== 2) {
                    continue;
                    // 跳过格式不正确的字段
                }
                $sqlFields[] = $this->escapeField($field[0]) . ' AS ' . $this->escapeField($field[1]);

            } elseif ($field instanceof Closure) {
                // 允许用闭包函数传入一个自定义的字段
                $sqlFields[] = $field();

            } else {
                // 处理普通字段
                $sqlFields[] = $this->escapeField($field);
            }
        }

        if (empty($sqlFields)) {
            throw new InvalidArgumentException("没有有效的字段可供选择，如果要选择所有字段，请使用 '*'");
        }

        return implode(', ', $sqlFields);
    }

    private function escapeField($field): string
    {
        // 处理别名
        if (is_string($field))
            $field = explode(' as ', preg_replace('/\s+(AS)?\s+/i', " as ", $field));
        if (count($field) > 2) {
            throw new InvalidArgumentException("字段参数错误");
        }
        // 处理点分割的库名表名
        $field = array_map(function ($item) {
            $parts = explode('.', $item);
            foreach ($parts as &$part) {
                if ($part !== '*') {
                    $part = '`' . addslashes(trim($part)) . '`';
                }
            }
            return implode('.', $parts);
        }, $field);

        return implode(' as ', $field);
    }

    private function setLastSql($sql, $params): void
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;
        $this->restore();
    }

    public function getLastSql($build = false): object|null
    {
        if (empty($this->lastSql)) {
            return null;
        }

        if (!$build) {
            return (object) [
                'sql' => $this->lastSql,
                'params' => $this->lastParams
            ];
        }

        // 替换占位符为实际参数值
        $sql = $this->lastSql;
        foreach ($this->lastParams as $param) {
            $value = is_string($param) ? "'" . addslashes($param) . "'" : $param;
            $pos = strpos($sql, '?');
            if ($pos !== false) {
                $sql = substr_replace($sql, $value, $pos, 1);
            }
        }

        return (object) [
            'sql' => $this->lastSql,
            'params' => $this->lastParams,
            'build' => $sql
        ];
    }

    public function page($page = 1, $pageSize = 20): static
    {
        $page = max(1, intval($page));
        $pageSize = max(1, intval($pageSize));
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->limit = $pageSize;
        $this->offset = ($page - 1) * $pageSize;
        return $this;
    }

    public function limit($limit, $offset = 0): static
    {
        $this->limit = max(0, intval($limit));
        $this->offset = max(0, intval($offset));
        // 重置page相关参数，因为直接设置了limit
        $this->page = null;
        $this->pageSize = null;
        return $this;
    }

    public function paginate($page = 1, $pageSize = 20): Builder
    {
        // 设置分页
        $this->page($page, $pageSize);
        return $this->select();
    }

    public function count(): Builder
    {
        $table = $this->getTableWithAlias();

        [$joins, $joinParams] = $this->buildJoin();
        [$whereSql, $whereParams] = $this->buildWhere();
        $params = array_merge($joinParams, $whereParams);

        $sql = "SELECT COUNT(*) as total FROM {$table}{$joins}{$whereSql}";
        $this->setLastSql($sql, $params);

        return $this;
    }

    public function fillableFields($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $fillable = empty($this->fillable) ? $this->fields : $this->fillable;

        $fields = array_change_key_case(array_flip($fillable), CASE_LOWER);

        // 处理二维数组
        if (isset($data[0]) && is_array($data[0])) {
            $result = [];
            foreach ($data as $item) {
                $item = array_change_key_case($item, CASE_LOWER);
                $result[] = array_intersect_key($item, $fields);
            }
            return $result;
        }

        // 处理一维数组
        $data = array_change_key_case($data, CASE_LOWER);
        return array_intersect_key($data, $fields);
    }

    public function join($table, $on, $type = 'INNER'): static
    {
        if (!in_array(strtoupper($type), ['LEFT', 'RIGHT', 'INNER', 'CROSS'])) {
            throw new InvalidArgumentException("`{$type}` 不支持的 join 类型");
        }

        $this->join[] = [
            'table' => $table,
            'on' => $on,
            'type' => $type,
        ];
        return $this;
    }

    public function leftJoin($table, $on): Builder
    {
        return $this->join($table, $on, 'LEFT');
    }

    public function rightJoin($table, $on): Builder
    {
        return $this->join($table, $on, 'RIGHT');
    }

    public function innerJoin($table, $on): Builder
    {
        return $this->join($table, $on, 'INNER');
    }

    public function crossJoin($table, $on): Builder
    {
        return $this->join($table, $on, 'CROSS');
    }

    private function buildJoin(): array
    {
        // 我在想，join的on条件是否需要支持数组和参数化
        $sql = '';
        $params = [];
        foreach ($this->join as $item) {

            if (is_array($item['table']) && count($item['table']) === 2) {
                $table = $item['table'][0];
                $alias = $item['table'][1];
            } elseif (is_array($item['table']) && count($item['table']) === 1) {
                $table = $item['table'][0];
                $alias = null;
            } elseif (is_string($item['table'])) {
                $table = $item['table'];
                $alias = null;
            }

            if (is_string($table)) {
                $table = "{$this->prefix}{$table}";

            } elseif ($table instanceof Builder) {
                $subQuery = $table->getLastSql();
                if (empty($subQuery))
                    throw new InvalidSqlException("join 子查询未构建");
                $table = "({$subQuery->sql} )";
                $params = array_merge($params, $subQuery->params);
            } else {
                throw new InvalidSqlException("join 表格式不正确");
            }

            // 处理表别名
            $table .= $alias ? " AS {$alias}" : '';

            $sql .= " {$item['type']} JOIN {$table} ON {$item['on']}";
        }

        return [$sql, $params];
    }

    public function getTableWithAlias($alias = null): string
    {
        $table = $this->prefix . $this->tableName;
        if (($this->alias || $alias) && $alias !== "") {
            $alias = $alias ?: $this->alias;
            return "{$table} AS {$alias}";
        }
        return $table;
    }

    public function forUpdate(): static
    {
        $this->forUpdate = true;
        return $this;
    }

    public function getPrimarys()
    {
        return $this->primarys;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function query(): mixed
    {
        $last = $this->getLastSql();
        if (!empty($last)) {
            return $this->connectPool->master()->query($last->sql, $last->params);
        } else {
            return null;
        }
    }

    public function execute(): int
    {
        $last = $this->getLastSql();
        if (!empty($last)) {
            return $this->connectPool->master()->execute($last->sql, $last->params);
        } else {
            return 0;
        }
    }

    /* 重置查询参数，准备好下一次查询 */
    private function restore(): void
    {
        // select
        $this->select = [];
        // where
        $this->where = [];
        $this->currentGroup = [];
        $this->groups = [];
        // order by
        $this->orderByConditions = [];
        // group by
        $this->groupByColumns = [];
        // limit   
        $this->limit = null;
        $this->offset = null;
        $this->page = null;
        $this->pageSize = null;
        // join
        $this->join = [];

    }

    /* 魔术方法 */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value): never
    {
        throw new \Exception("属性是只读的");
    }

    public function __unset($name)
    {
        throw new \Exception("属性是只读的");
    }
    
    public function __debugInfo(): array
    {
        return [
            "prefix" => $this->prefix,
            "tableName" => $this->tableName,
            "primarys" => $this->primarys,
            "fillable" => !empty($this->fillable) ? $this->fillable : $this->fields,
            "fields" => $this->fields,
            "lastSql" => $this->lastSql,
            "lastParams" => $this->lastParams,
            "limit" => $this->limit,
            "offset" => $this->offset,
            "page " => $this->page,
            "pageSize " => $this->pageSize,
            "total" => empty($this->records) ? 0 : $this->records->count()
        ];
    }
}
