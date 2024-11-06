<?php

/**
 * @description 数据模型模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 */

namespace Sharky\Core;

class Model
{
    protected $tableName;
    protected $primarys = ['id'];
    protected $db;
    protected $where = [];
    protected $currentGroup = null;
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

    public function __construct()
    {
        // 如果未指定表名，使用类名
        if (empty($this->tableName)) {
            $className = get_class($this);
            $className = substr($className, strrpos($className, '\\') + 1);
            $this->tableName = strtolower(str_replace('Model', '', $className));
        }

        // 获取数据库实例(建议使用容器复用数据库连接)
        $this->db = Container::getInstance()->make('database');
        // $this->db = new Database();

        // 获取表字段
        $this->fields = $this->db->getFields($this->tableName);
    }

    public function where($conditions, $operator = 'AND')
    {
        if (!is_array($conditions)) {
            return $this;
        }

        if ($this->currentGroup === null) {

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
                $this->groups[$this->currentGroup][] = ['condition' => $conditions, 'operator' => $operator];
            } else {
                foreach ($conditions as $condition) {
                    $this->groups[$this->currentGroup][] = ['condition' => $condition, 'operator' => $operator];
                }
            }
        }

        return $this;
    }

    protected function normalizeWhere($conditions)
    {
        $normalized = [];

        // 单条件解析
        if (count($conditions) === 2 && is_string($conditions[0])) {
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

    public function whereOr($conditions)
    {
        return $this->where($conditions, 'OR');
    }

    public function beginGroup($operator = 'AND')
    {
        $groupId = uniqid('group_');
        $this->groups[$groupId] = [];
        $this->where[] = ['type' => 'group', 'id' => $groupId, 'operator' => $operator];
        $this->currentGroup = $groupId;
        return $this;
    }

    public function endGroup()
    {
        $this->currentGroup = null;
        return $this;
    }

    protected function buildWhere()
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
                    $field = preg_match('/^\d+$/', $condition[0])? $condition[0] : $this->escapeField($condition[0]);
                    $whereSql .= "{$field} {$condition[1]} ?";
                    $params[] = $condition[2];
                    $firstCondition = false;
                }
            }
        }

        return [$whereSql, $params];
    }

    protected function buildGroupConditions($conditions, &$params)
    {
        $sql = '';
        $firstCondition = true;
        foreach ($conditions as $item) {
            if (!$firstCondition) {
                $sql .= " {$item['operator']} ";
            }

            $condition = $item['condition'];
            // 允许使用整数，否过滤并加反引号                    
            $field = preg_match('/^\d+$/', $condition[0])? $condition[0] : $this->escapeField($condition[0]);
            $sql .= "{$field} {$condition[1]} ?";
            $params[] = $condition[2];
            $firstCondition = false;
        }

        return $sql;
    }

    public function find($id = null)
    {
        if ($id !== null) {
            $this->where(['id', '=', $id]);
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

    public function select($fields = null)
    {
        // 构建SQL
        $fields = ($fields !== null) ? $fields : $this->filter;
        $fieldsSql = $this->buildFields($fields);
        list($whereSql, $params) = $this->buildWhere();
        $sql = "SELECT {$fieldsSql} FROM {$this->tableName}" . $whereSql;
        // 添加 LIMIT 和 OFFSET
        if ($this->limit !== null && $this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset > 0) {
                $sql .= " OFFSET " . $this->offset;
            }
        }

        $this->setLastSql($sql, $params);

        $results = $this->db->query($sql, $params);

        // 清空 records 并将每个记录转换为 Model 实例
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
        // 过滤无效字段
        $data = $this->filterFields($data);
        if (empty($data)) {
            return false;
        }

        // 处理批量插入
        if (isset($data[0]) && is_array($data[0])) {
            $fields = array_keys($data[0]);
            $values = [];
            $params = [];
            foreach ($data as $row) {
                $placeholders = [];
                foreach ($fields as $field) {
                    $placeholders[] = '?';
                    $params[] = $row[$field];
                }
                $values[] = '(' . implode(',', $placeholders) . ')';
            }

            $sql = "INSERT INTO {$this->tableName} (" .
                implode(',', $fields) .
                ") VALUES " .
                implode(',', $values);
            $this->setLastSql($sql, $params);
            return $this->db->execute($sql, $params);
        } else {
            // 单条插入
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO {$this->tableName} (" .
                implode(',', $fields) .
                ") VALUES (" .
                implode(',', $placeholders) .
                ")";

            $this->setLastSql($sql, array_values($data));
            return $this->db->execute($sql, array_values($data));
        }
    }

    public function update($data)
    {
        // 过滤无效字段
        $data = $this->filterFields($data);
        if (empty($data)) {
            return false;
        }

        $sets = [];
        $params = [];
        foreach ($data as $field => $value) {
            $sets[] = "$field = ?";
            $params[] = $value;
        }

        list($whereSql, $whereParams) = $this->buildWhere();
        $params = array_merge($params, $whereParams);
        if ($whereSql === "") {
            throw new \Exception("为了安全起见，不允无条件更新");
        }
        $sql = "UPDATE {$this->tableName} SET " . implode(',', $sets) . $whereSql;
        $this->setLastSql($sql, $params);
        return $this->db->execute($sql, $params);
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

    public function delete()
    {
        list($whereSql, $params) = $this->buildWhere();
        if ($whereSql === "") {
            throw new \Exception("为了安全起见，不允无条件删除");
        }
        $sql = "DELETE FROM {$this->tableName}" . $whereSql;

        $this->setLastSql($sql, $params);
        return $this->db->execute($sql, $params);
    }

    public function fields($fields)
    {
        if (empty($fields)) {
            $this->filter = ['*'];
            return $this;
        }

        if (is_string($fields)) {
            // 如果是字符串，按逗号分割
            $this->filter = array_map('trim', explode(',', $fields));
        } elseif (is_array($fields)) {
            $this->filter = $fields;
        }

        return $this;
    }

    protected function buildFields($fields)
    {
        if (empty($fields) || (is_array($fields) && count($fields) === 1 && $fields[0] === '*')) {
            return '*';
        }

        $sqlFields = [];
        $fields = is_array($fields) ? $fields : explode(', ', $fields);
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

        return empty($sqlFields) ? '*' : implode(', ', $sqlFields);
    }

    protected function escapeField($field)
    {
        // 移除所有不安全的字符
        $field = preg_replace('/[^a-zA-Z0-9_\.]/', '', $field);
        // 处理可能包含表名的字段 (table.field)
        $parts = explode('.', $field);
        foreach ($parts as &$part) {
            if ($part !== '*') {
                $part = '`' . $part . '`';
            }
        }

        return implode('.', $parts);
    }

    protected function setLastSql($sql, $params)
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;
    }

    public function getLastSql($withParams = true)
    {
        if (!$withParams) {
            return $this->lastSql;
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
            'raw_sql' => $this->lastSql,
            'params' => $this->lastParams
        ];
    }

    /*
     * 设置分页
     * @param int $page 页码，从1开始
     * @param int $pageSize 每页数量
     * @return $this
     */
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

    /**
     * 执行分页查询
     * @param string|array|null $fields
     * @return array 包含分页信息和数据的数组
     */
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
            'data' => $list
        ];
        // 如果是使用page()方法设置的分页
        if ($this->page !== null) {
            $pageInfo = array_merge($pageInfo, [
                'current_page' => $this->page,
                'page_size' => $this->pageSize,
                'total_pages' => ceil($total / $this->pageSize),
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


    public function limit($limit, $offset = 0)
    {
        $this->limit = max(0, intval($limit));
        $this->offset = max(0, intval($offset));
        // 重置page相关参数，因为直接设置了limit
        $this->page = null;
        $this->pageSize = null;
        return $this;
    }

    public function count()
    {
        list($whereSql, $params) = $this->buildWhere();
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName}" . $whereSql;
        $this->setLastSql($sql, $params);
        $result = $this->db->query($sql, $params);
        return isset($result[0]['total']) ? intval($result[0]['total']) : 0;
    }

    protected function filterFields($data)
    {
        if (empty($data)) {
            return [];
        }

        // 处理二维数组
        if (isset($data[0]) && is_array($data[0])) {
            $result = [];
            foreach ($data as $row) {
                $result[] = array_intersect_key($row, array_flip($this->fields));
            }
            return $result;
        }

        // 处理一维数组
        return array_intersect_key($data, array_flip($this->fields));
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function __debugInfo()
    {
        return [
            "tableName" => $this->tableName,
            "primarys" => $this->primarys,
            "fields" => $this->fields,
            "filter" => $this->filter,
            "lastSql" => $this->lastSql,
            "lastParams" => $this->lastParams,
            "limit" => $this->limit,
            "offset" => $this->offset,
            "page " => $this->page,
            "pageSize " => $this->pageSize,
            "attributes" => $this->attributes,
        ];
    }
}
