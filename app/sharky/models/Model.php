<?php

/**
 * @description 数据模型模块
 * @author Sharky
 * @date 2024-11-1
 * @version 1.0.0
 *
 * 防止后续调整Sharky\Core\Model类导致的不兼容
 * 这里全部重新它以便后期有需要时调整兼容性
 */

namespace App\Models;

use Sharky\Core\Model as BaseModel;

class Model extends BaseModel
{
    protected $tableName;

    public function orderBy($columns) {
        return parent::orderBy($columns);
    }

    public function groupBy($columns) {
        return parent::groupBy($columns);
    }

    public function where($conditions, $operator = 'AND')
    {
        return parent::where($conditions, $operator);
    }

    public function whereOr($conditions)
    {
        return parent::whereOr($conditions);
    }

    public function beginGroup($operator = 'AND')
    {
        return parent::beginGroup($operator);
    }

    public function endGroup()
    {
        return parent::endGroup();
    }

    protected function buildWhere()
    {
        return parent::buildWhere();
    }

    public function find($id = null)
    {
        return parent::find($id);
    }

    public function select($fields = null)
    {
        return parent::select($fields);
    }

    public function insert($data)
    {
        return parent::insert($data);
    }

    public function update($data)
    {
        return parent::update($data);
    }

    public function delete()
    {
        return parent::delete();
    }

    public function fields($fields)
    {
        return parent::fields($fields);
    }

    protected function buildFields($fields)
    {
        return parent::buildFields($fields);
    }

    protected function escapeField($field)
    {
        return parent::escapeField($field);
    }

    public function save($data = [])
    {
        return parent::save($data);
    }

    public function page($page = 1, $pageSize = 20)
    {
        return parent::page($page, $pageSize);
    }

    public function paginate($fields = null)
    {
        return parent::paginate($fields);
    }


    public function limit($limit, $offset = 0)
    {
        return parent::limit($limit, $offset);
    }

    public function count()
    {
        return parent::count();
    }

    public function beginTransaction()
    {
        return parent::beginTransaction();
    }

    protected function buildGroupConditions($conditions, &$params)
    {
        return parent::buildGroupConditions($conditions, $params);
    }

    protected function filterFields($data)
    {
        return parent::filterFields($data);
    }

    public function commit()
    {
        return parent::commit();
    }

    public function rollback()
    {
        return parent::rollback();
    }
}
