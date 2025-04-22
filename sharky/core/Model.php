<?php

/**
 * @description 数据模型模块
 * @author Sharky
 * @date 2025-4-23
 * @version 1.3.0
 */

/**
 * 注意事项
 * 1、where 分组问题 待验证
 * 2、jion 查询问题 待验证
 */

namespace Sharky\Core;

use Exception;

class Model
{
    protected $config;
    protected $tableName;
    protected $primarys = [];
    protected $where = [];
    protected $groupByColumns = [];
    protected $orderByConditions = [];
    protected $currentGroup = [];
    protected $groups = [];
    protected $fields = [];
    protected $filter = ['*'];
    protected $lastSql = '';
    protected $lastParams = [];
    protected $limit = null;
    protected $offset = null;
    protected $page = null;
    protected $pageSize = null;
    protected $attributes = [];
    protected $records = [];
    protected $database = null;
    protected $prefix = null;
    protected $alias = null;
    protected $join = [];
    protected $builderOnly = false;

    public function __construct($database = 'default')
    {
        $this->config = Container::getInstance()
            ->make('config')
            ->get('database.' . $database);

        if (empty($this->config)) {
            throw new Exception("未找到数据库配置");
        }

        if (!isset($this->prefix)) {
            $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : 'sharky_';
        }

        // 如果未指定表名，使用类名
        if (empty($this->tableName)) {
            $this->tableName = $this->generateTableName();
        }

        // 获取表全部字段和主键字段
        $this->database = Database::connects($database);
        $this->fields = $this->database->slave()->getFields($this->prefix . $this->tableName);
        $this->primarys = $this->database->slave()->getPrimarys($this->prefix . $this->tableName);
    }

    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function table($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function orderBy($columns)
    {
        if (is_string($columns)) {
            $this->orderByConditions[] = $columns;
        } elseif (is_array($columns)) {
            foreach ($columns as $column => $direction) {
                if (is_numeric($column)) {
                    $this->orderByConditions[] = $direction;
                } else {
                    $this->orderByConditions[] = "$column $direction";
                }
            }
        }
        return $this;
    }

    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $this->groupByColumns[] = $columns;
        } elseif (is_array($columns)) {
            $this->groupByColumns = array_merge($this->groupByColumns, $columns);
        }
        return $this;
    }

    public function where($conditions, $operator = 'AND')
    {
        if (!is_array($conditions)) {
            return $this;
        }

        if (empty($this->currentGroup)) {

            // 规范where
            $conditions = $this->normalizeWhere($conditions);

            // 不在分组中，直接添加到主where数组
            if (isset($conditions[0]) && !is_array($conditions[0])) {
                $this->where[] = ['condition' => $conditions, 'operator' => $operator];
            } else {
                foreach ($conditions as $condition) {
                    $this->where[] = ['condition' => $condition, 'operator' => $operator];
                }
            }
        } else {
            // 在分组中，添加到当前分组
            if (isset($conditions[0]) && !is_array($conditions[0])) {
                $this->groups[end($this->currentGroup)][] = ['condition' => $conditions, 'operator' => $operator];
            } else {
                foreach ($conditions as $condition) {
                    $this->groups[end($this->currentGroup)][] = ['condition' => $condition, 'operator' => $operator];
                }
            }
        }

        return $this;
    }

    public function whereOr($conditions)
    {
        return $this->where($conditions, 'OR');
    }

    private function normalizeWhere($conditions)
    {
        $normalized = [];

        // 单条件解析
        if (count($conditions) === 2 && isset($conditions[0]) && is_string($conditions[0])) {
            // 格式：['字段名', '值']
            $normalized[] = [$conditions[0], "=", $conditions[1]];

        } elseif (count($conditions) === 3 && is_string($conditions[0]) && is_string($conditions[1])) {
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

                } elseif (is_array($value) && count($value) === 2 && is_string($value[0])) {
                    // 格式：[['字段名', '值'], ...]
                    $normalized[] = [$value[0], "=", $value[1]];

                } elseif (is_array($value) && count($value) === 3 && is_string($value[0]) && is_string($value[1])) {
                    // 格式：[['字段名', '运算符', '值'], ...]
                    $normalized[] = [$value[0], $value[1], $value[2]];

                }
            }
        }
        return $normalized;
    }

    private function buildWhere()
    {
        $whereSql = '';
        $params = [];
        if (!empty($this->where)) {
            $whereSql .= ' WHERE ';
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
                    $whereSql .= "{$field} {$condition[1]} ?";
                    $params[] = $condition[2];
                    $firstCondition = false;
                }
            }
        }

        return [$whereSql, $params];
    }

    public function beginGroup($operator = 'AND')
    {
        $groupId = uniqid('group_');
        $this->groups[$groupId] = [];
        $this->where[] = ['type' => 'group', 'id' => $groupId, 'operator' => $operator];
        $this->currentGroup[] = $groupId;
        return $this;
    }

    public function endGroup()
    {
        array_pop($this->currentGroup);
        return $this;
    }

    public function whereGroup($callback, $operator = 'AND')
    {
        $this->beginGroup($operator);
        call_user_func($callback, $this);
        $this->endGroup();
        return $this;
    }

    private function buildGroupConditions($conditions, &$params)
    {
        $sql = '';
        $firstCondition = true;
        foreach ($conditions as $item) {
            if (!$firstCondition) {
                $sql .= " {$item['operator']} ";
            }

            $condition = $item['condition'];
            // 允许使用整数，否过滤并加反引号                    
            $field = preg_match('/^\d+$/', $condition[0]) ? $condition[0] : $this->escapeField($condition[0]);
            $sql .= "{$field} {$condition[1]} ?";
            $params[] = $condition[2];
            $firstCondition = false;
        }

        return $sql;
    }

    public function find($id = null, $key = 'id')
    {
        return $this->first($id, $key);
    }

    public function first($id = null, $key = 'id')
    {
        if ($id !== null) {
            $this->where([$key, '=', $id]);
        }

        $result = $this->limit(1)->select();
        $result = $result->toArray();

        if (empty($result)) {
            return false;
        } else {
            $model = new static();
            $model->attributes = $result[0];
            return $model;
        }
    }

    public function fields($fields)
    {
        // 废弃隐式指定 * 
        // 如果使用 * 必须显示指定
        // 可用二维数组指定字段和别名
        // 例如： [['field1', 'alias1'], ['field2', 'alias2']]
        // 例如： ['field1', 'field2']
        // 例如： 'field1, field2'
        // 例如： 'field1, field2 AS alias2'
        // if (empty($fields)) {
        //     $this->filter = ['*'];
        // }
        if (is_string($fields)) {
            $this->filter = array_map('trim', explode(',', $fields));
        } elseif (is_array($fields)) {
            $this->filter = $this->arrayMap('trim', $fields);
        }
        return $this;
    }

    public function lastinsertId()
    {
        return $this->database->master()->lastInsertId();
    }

    public function select($fields = null)
    {
        $fields = ($fields !== null) ? $fields : $this->filter;
        $fieldsSql = $this->buildFields($fields);

        $groupBy = '';
        $orderBy = '';
        $table = $this->getTableWithAlias();

        list($joins, $jionParams) = $this->buildJoin();
        list($whereSql, $whereParams) = $this->buildWhere();
        $params = array_merge($jionParams, $whereParams);

        if (!empty($this->groupByColumns)) {
            $groupBy = " GROUP BY " . implode(', ', $this->groupByColumns);
        }
        if (!empty($this->orderByConditions)) {
            $orderBy = " ORDER BY " . implode(', ', $this->orderByConditions);
        }

        $sql = "SELECT {$fieldsSql} FROM {$table}{$joins}{$whereSql}{$groupBy}{$orderBy}";

        // 添加 LIMIT 和 OFFSET
        if ($this->limit !== null && $this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset > 0) {
                $sql .= " OFFSET " . $this->offset;
            }
        }

        $this->setLastSql($sql, $params);

        if ($this->builderOnly) {
            return $this;
        }

        $results = $this->database->slave()->query($sql, $params);
        $this->records = new Collection();
        $baseModel = new static();
        foreach ($results as $result) {
            $model = clone $baseModel;
            $model->attributes = $result;
            $this->records->add($model);
        }

        return $this->records;
    }

    public function insert($data)
    {
        if (is_array($data)) {
            return $this->insertArray($data);

        } elseif ($data instanceof Model) {

            if ($data->isSubQuery()) {
                // 编译子查询
                $sql = $data->getLastSql();
                if (empty($sql)) {
                    throw new Exception("子查询没有有效的SQL语句");
                }

                $data = " ({$sql['raw']})";
                $params = $sql['params'];
                $table = $this->getTableWithAlias("");

                $fieldsSql = $this->buildFields($this->filter);
                $fieldsSql = $fieldsSql ? " ({$fieldsSql})" : "";

                $sql = "INSERT INTO {$table}{$fieldsSql}{$data}";

                $this->setLastSql($sql, $params);

                if ($this->builderOnly) {
                    return $this;
                }

                return $this->database->master()->execute($sql, $params);
            }
            // 处理 Model 对象, 单条插入
            $insertData = $data->toArray();
            return $this->insertArray($insertData);

        } elseif ($data instanceof Collection) {
            // 处理 Collection 对象，批量插入
            $insertData = $data->toArray();
            return $this->insertArray($insertData);

        } else {
            throw new Exception("数据格式不正确");
        }
    }

    private function insertArray($data)
    {
        // 过滤无效字段
        $data = $this->filterFields($data);
        if (empty($data)) {
            return false;
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
                    return "`" . addslashes($item) . "`";
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
                    return "`" . addslashes($item) . "`";
                }, $fields)) .
                ") VALUES (" .
                implode(', ', $placeholders) .
                ")";

            $this->setLastSql($sql, array_values($data));
            $params = array_values($data);
        }

        if ($this->builderOnly) {
            return $this;
        }

        return $this->database->master()->execute($sql, $params);
    }

    public function update($data)
    {
        // 过滤无效字段
        $data = $this->filterFields($data);
        if (empty($data)) {
            return false;
        }

        list($joins, $jionParams) = $this->buildJoin();
        list($whereSql, $whereParams) = $this->buildWhere();

        $table = $this->getTableWithAlias();
        $sets = [];
        $setParams = [];
        foreach ($data as $field => $value) {

            $field = ($this->alias ? "`{$this->alias}`." : ""). "`" . addslashes($field) . "`";

            if (is_array($value)) {
                // 处理数组值
                $value = implode(
                    '.',
                    array_map(function ($item) {
                        return "`" . addslashes($item) . "`";
                    }, $value)
                );
                $sets[] = "$field = $value";
            } else {
                $sets[] = "$field = ?";
                $setParams[] = $value;
            }
        }


        $params = array_merge($jionParams, $setParams, $whereParams);
        if ($whereSql === "") {
            throw new Exception("为了安全起见，不允无条件更新");
        }
        $sql = "UPDATE {$table}{$joins} SET " . implode(',', $sets) . $whereSql;
        $this->setLastSql($sql, $params);

        if ($this->builderOnly) {
            return $this;
        }
        return $this->database->master()->execute($sql, $params);
    }

    public function delete()
    {

        list($joins, $jionParams) = $this->buildJoin();
        list($whereSql, $whereParams) = $this->buildWhere();
        $params = array_merge($jionParams, $whereParams);

        if ($whereSql === "") {
            throw new Exception("为了安全起见，不允无条件删除");
        }

        if (empty($joins)) {
            $table = $this->getTableWithAlias("");
            $sql = "DELETE FROM {$table}" . $whereSql;
        } else {
            $table = $this->getTableWithAlias();
            $sql = "DELETE {$this->alias} FROM {$table}{$joins}" . $whereSql;
        }

        $this->setLastSql($sql, $params);

        if ($this->builderOnly) {
            return $this;
        }
        return $this->database->master()->execute($sql, $params);
    }

    public function save($data = [])
    {
        // 过滤无效字段
        $data = $this->filterFields($data);
        if (empty($data)) {
            return false;
        }

        // 根据主键生成条件
        $wheres = [];
        if (!empty($this->attributes)) {
            foreach ($this->primarys as $primary) {
                if (in_array($primary, $this->fields)) {
                    $wheres[$primary] = $this->attributes[$primary];
                }
            }
        }

        // 修改模型数据
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }

        if (!empty($wheres)) {
            return $this->where($wheres)->update($data);
        } else {
            return $this->insert($data);
        }
    }

    private function arrayMap($callback, $array)
    {
        return array_map(function ($item) use ($callback) {
            return is_array($item) ? $this->arrayMap($callback, $item) : $callback($item);
        }, $array);
    }

    private function buildFields($fields)
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
            } else {
                // 处理普通字段
                $sqlFields[] = $this->escapeField($field);
            }
        }

        if (empty($sqlFields)) {
            throw new Exception("没有有效的字段可供选择，如果要选择所有字段，请使用 '*'");
        }

        return implode(', ', $sqlFields);
    }

    private function escapeField($field)
    {
        // 移除所有不安全的字符
        // $field = preg_replace('/[^a-zA-Z0-9_\.]/', '', $field);
        // 处理可能包含表名的字段 (table.field)
        $parts = explode('.', $field);
        foreach ($parts as &$part) {
            if ($part !== '*') {
                $part = '`' . addslashes($part) . '`';
            }
        }

        return implode('.', $parts);
    }

    private function setLastSql($sql, $params)
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;
    }

    public function getLastSql($build = false)
    {
        if (empty($this->lastSql)) {
            return null;
        }

        if (!$build) {
            return [
                'raw' => $this->lastSql,
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

        return [
            'sql' => $sql,
            'raw' => $this->lastSql,
            'params' => $this->lastParams
        ];
    }

    public function page($page = 1, $pageSize = 20)
    {
        $page = max(1, intval($page));
        $pageSize = max(1, intval($pageSize));
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->limit = $pageSize;
        $this->offset = ($page - 1) * $pageSize;
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->limit = max(0, intval($limit));
        $this->offset = max(0, intval($offset));
        // 重置page相关参数，因为直接设置了limit
        $this->page = null;
        $this->pageSize = null;
        return $this;
    }

    public function paginate($fields = null)
    {
        // 如果没有设置分页，使用默认值
        if ($this->page === null && $this->limit === null) {
            $this->page(1);
        }

        $total = $this->count();
        $list = $this->select($fields);
        $pageInfo = [
            'total' => $total,
            'data' => $list->toArray()
        ];
        // 如果是使用page()方法设置的分页
        if ($this->page !== null) {
            $total_pages = ceil($total / $this->pageSize);
            $pageInfo = array_merge($pageInfo, [
                'current_page' => min($this->page, $total_pages),
                'page_size' => $this->pageSize,
                'total_pages' => $total_pages,
                'has_more' => ($this->page * $this->pageSize) < $total
            ]);
        }

        // 如果是使用limit()方法设置的分页
        if ($this->limit !== null && $this->limit > 0) {
            $pageInfo = array_merge($pageInfo, [
                'limit' => $this->limit,
                'offset' => $this->offset
            ]);
        }

        return $pageInfo;
    }

    public function count()
    {
        $table = $this->getTableWithAlias();

        list($joins, $jionParams) = $this->buildJoin();
        list($whereSql, $whereParams) = $this->buildWhere();
        $params = array_merge($jionParams, $whereParams);

        $sql = "SELECT COUNT(*) as total FROM {$table}{$joins}{$whereSql}";
        $this->setLastSql($sql, $params);

        if ($this->builderOnly) {
            return $this;
        }

        $result = $this->database->slave()->query($sql, $params);
        return isset($result[0]['total']) ? intval($result[0]['total']) : 0;
    }

    protected function filterFields($data)
    {
        if (!is_array($data)) {
            return [];
        }

        if (empty($this->fields)) {
            return $data;
        }

        // 处理二维数组
        if (isset($data[0]) && is_array($data[0])) {
            $result = [];
            foreach ($data as $item) {
                $result[] = array_intersect_key($item, array_flip($this->fields));
            }
            return $result;
        }

        // 处理一维数组
        return array_intersect_key($data, array_flip($this->fields));
    }

    public function jion($table, $on, $type = 'INNER')
    {
        if(!in_array(strtolower($type), [])){
            throw new Exception("`{$type}` 不支持的 Jion");
        }

        $this->join[] = [
            'table' => $table,
            'on' => $on,
            'type' => $type,
        ];
        return $this;
    }

    public function leftJoin($table, $on)
    {
        return $this->jion($table, $on, 'LEFT');
    }

    public function rightJoin($table, $on)
    {
        return $this->jion($table, $on, 'RIGHT');
    }

    public function innerJoin($table, $on)
    {
        return $this->jion($table, $on, 'INNER');
    }

    public function fullJoin($table, $on)
    {
        return $this->jion($table, $on, 'FULL');
    }

    private function buildJoin()
    {
        // 我在想，jion的on条件是否需要支持数组和参数化
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

            } elseif ($table instanceof Model) {
                if ($table->isSubQuery()) {
                    $lastSql = $table->getLastSql();
                    if (empty($lastSql)) {
                        throw new Exception("jion 子查詢不完整");
                    }
                    $table = "({$lastSql['raw']} )";
                    $params = array_merge($params, $lastSql['params']);
                } else {
                    throw new Exception("不支持 jion 非子查询的模型");
                }
            } else {
                throw new Exception("jion 表格式不正确");
            }

            // 处理表别名
            $table .= $alias ? " AS {$alias}" : '';

            $sql .= " {$item['type']} JOIN {$table} ON {$item['on']}";
        }

        return [$sql, $params];
    }

    public function toArray()
    {
        return $this->attributes;
    }

    /*
     * 返回一个克隆体，用于生成子查询
     * 该克隆体不会执行查询
     * @return Builder
     */
    public function subQuery()
    {
        $subQuery = clone $this;
        $subQuery->builderOnly = true;
        return $subQuery;
    }

    public function isSubQuery()
    {
        return $this->builderOnly;
    }

    private function generateTableName()
    {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        $tableName = str_replace('Model', '', $className);
        $pattern = '/(?<!^)[A-Z]/';
        $tableName = preg_replace($pattern, '_$0', $tableName);
        return strtolower($tableName);
    }

    public function getTableWithAlias($alias = null)
    {
        $table = $this->prefix . $this->tableName;
        if (($this->alias || $alias) && $alias !== "") {
            $alias = $alias ?: $this->alias;
            return "{$table} AS {$alias}";
        }
        return $table;
    }

    /* 魔术方法 */
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __debugInfo()
    {
        return [
            "prefix" => $this->prefix,
            "tableName" => $this->tableName,
            "primarys" => $this->primarys,
            "filter" => $this->filter,
            "fields" => $this->fields,
            "lastSql" => $this->lastSql,
            "lastParams" => $this->lastParams,
            "limit" => $this->limit,
            "offset" => $this->offset,
            "page " => $this->page,
            "pageSize " => $this->pageSize,
            "attributes" => $this->attributes,
            "total" => count($this->records),
        ];
    }
}
