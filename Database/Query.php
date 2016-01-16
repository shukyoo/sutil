<?php namespace Sutil\Database;

class Query
{
    /**
     * @var Connection
     */
    protected $_connection;
    /**
     * @var Grammar
     */
    protected $_grammar;

    protected $_sql = '';
    protected $_bind = [];

    protected $_table = '';
    protected $_join;
    protected $_distinct = false;
    protected $_selection = ['*'];
    protected $_where = '';
    protected $_group = [];
    protected $_order = [];
    protected $_limit;
    protected $_offset;

    public function __construct(Connection $connection, Grammar $grammar)
    {
        $this->_connection = $connection;
        $this->_grammar = $grammar;
    }

    /**
     * @param $table
     * @return $this
     */
    public function from($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function leftJoin($table, $on)
    {
        return $this->join('LEFT JOIN', $table, $on);
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function rightJoin($table, $on)
    {
        return $this->join('RIGHT JOIN', $table, $on);
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function innerJoin($table, $on)
    {
        return $this->join('INNER JOIN', $table, $on);
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function fullJoin($table, $on)
    {
        return $this->join('FULL JOIN', $table, $on);
    }

    /**
     * @param $type
     * @param $table
     * @param $on
     * ['a.id', '=', 'b.a_id']
     * ['on' => ['a.id', '=', 'b.a_id'], 'oron' => ['a.id', '=', 'b.a_id'], 'where'=>['b.name' => 'test']]
     * @return $this
     */
    public function join($type, $table, $on)
    {
        $this->_join[] = array(
            'type' => $type,
            'table' => $table,
            'on' => $this->_on($on)
        );
        return $this;
    }

    protected function _on($on)
    {
        if (is_string($on)) {
            return $on;
        } elseif (!empty($on[0]) && is_string($on[0])) {
            return $this->_onParse($on[0], $on[1], $on[2]);
        } else {
            $str = '';
            foreach ($on as $k=>$item) {
                if ($k == 'on') {
                    $str .= ($str ? ' AND ' : '');
                    $str .= $this->_onParse($item[0], $item[1], $item[2]);
                } elseif ($k == 'oron') {
                    $str .= ' OR ';
                    $str .= $this->_onParse($item[0], $item[1], $item[2]);
                } elseif ($k == 'where') {
                    $where = $this->_where($item);
                    $str .= ($str ? ' AND ('. $where .')' : $where);
                }
            }
            return $str;
        }
    }

    protected function _onParse($f1, $op, $f2)
    {
        return $this->_grammar->quoteIdent($f1) . $op . $this->_grammar->quoteIdent($f2);
    }

    /**
     * @return $this;
     */
    public function distinct()
    {
        $this->_distinct = true;
        return $this;
    }


    /**
     * @param array|string $selection
     * @return $this
     */
    public function select($selection)
    {
        $this->_selection = is_array($selection) ? $selection : func_get_args();
        return $this;
    }

    /**
     * Group part
     * @param string $field
     * @param string $having
     * @return $this
     */
    public function group($field, $having = null)
    {
        $this->_group[] = $field;
        if ($having) {
            $this->_group[] = $having;
        }
        return $this;
    }

    /**
     * orderBy('id', 'DESC')
     * orderBy(['id' => 'DESC', 'test' => 'ASC'])
     * @param string|array $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = null)
    {
        if (is_array($field)) {
            $this->_order = array_merge($this->_order, $field);
        } elseif ($direction) {
            $this->_order[$field] = $direction;
        } else {
            $this->_order[$field] = 'DESC';
        }
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderASC($field)
    {
        return $this->orderBy($field, 'ASC');
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderDESC($field)
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Set limit and offset
     * @param int $number
     * @param int $offset
     * @return $this
     */
    public function limit($number, $offset = null)
    {
        $this->_limit = (int)$number;
        if (null !== $offset) {
            $this->_offset = (int)$offset;
        }
        return $this;
    }

    /**
     * Set limit and offset by page
     * @param int $page
     * @param int $page_size
     * @return $this
     */
    public function page($page, $page_size = 20)
    {
        if ($page < 1) {
            $page = 1;
        }
        return $this->limit($page_size, (($page - 1) * $page_size));
    }



    /**
     * e.g.
    [
        'id=?' => 1,
        'hello' => 'world',
        'id' => [1,2,3],
        'name in?' => ['aa', 'bb'],
        'and' => [],
        'or' => []
    ]
     * @param mixed $where
     * @return $this
     */
    public function where($where)
    {
        if (!is_null($where)) {
            if ($this->_where) {
                $this->_where .= ' AND '. $this->_where($where);
            } else {
                $this->_where = $this->_where($where);
            }
        }
        return $this;
    }

    /**
     * @param $where
     * @return $this
     */
    public function andWhere($where)
    {
        if (!is_null($where)) {
            if ($this->_where) {
                $this->_where .= ' AND ('. $this->_where($where) .')';
            } else {
                $this->_where = $this->_where($where);
            }
        }
        return $this;
    }

    /**
     * @param $where
     * @return $this
     */
    public function orWhere($where)
    {
        if (!is_null($where)) {
            $this->_where .= ' OR ('. $this->_where($where) .')';
        }
        return $this;
    }

    /**
     * @param $where
     * @return string
     */
    public function _where($where)
    {
        if (!is_array($where)) {
            return $where;
        }
        $where_str = '';
        foreach ($where as $k => $v) {
            $co = is_int($k) ? 'AND' : strtoupper(trim($k));
            if ($co == 'AND' || $co == 'OR') {
                $co = $where_str ? " {$co} " : '';
                $where_str .= $co .'(' . $this->where($v) .')';
            } else {
                $co = $where_str ? " AND " : '';
                $where_str .= $co . $this->_whereParse($k, $v);
            }
        }
        return $where_str;
    }

    /**
     * ('id=?', 1)
     * ('age>=?', 22)
     * ('id in?', [1,2,3])
     * ('id not in?', [1,2])
     * @param string $condition
     * @param mixed $value
     * @return string
     */
    protected function _whereParse($condition, $value)
    {
        if (!strpos($condition, '?')) {
            if (is_array($value)) {
                return $this->_grammar->quoteIdent($condition) .' IN('. $this->_parseIn($value) .')';
            } else {
                $this->_bind($value);
                return $this->_grammar->quoteIdent($condition) .'=?';
            }
        }
        preg_match('/^(\w+)(\s+not)?(\s+\w+|\s*[!=><]+)\s*\?$/', $condition, $matches);
        if (empty($matches[3])) {
            throw new \InvalidArgumentException('invalid where condition');
        }
        $field = $this->_grammar->quoteIdent($matches[1]);
        $not = trim($matches[2]);
        $opt = strtolower(trim($matches[3]));
        $str = $field . ($not ? ' NOT' : '');
        switch ($opt) {
            case 'in':
                $str .= ' IN('. $this->_parseIn($value) .')';
                break;
            case 'between':
                $str .= ' BETWEEN '. $this->_parseBetween($value);
                break;
            case 'like':
                $str .= ' LIKE '. $this->_parseLike($value);
                break;
            default:
                $this->_bind($value);
                $str .= $opt .'?';
                break;
        }
        return $str;
    }

    /**
     * @param $value
     * @return string
     */
    protected function _parseIn($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $this->_bind($value);
        return implode(',', array_fill(0, count($value), '?'));
    }

    /**
     * @param $value
     * @return string
     */
    protected function _parseBetween($value)
    {
        if (!is_array($value)) {
            $value = explode(',', $value);
        } else {
            $value = array_values($value);
        }
        if (!isset($value[1])) {
            throw new \InvalidArgumentException('invalid between condition');
        }
        $this->_bind($value);
        return '? AND ?';
    }

    /**
     * @param $value
     * @return string
     */
    protected function _parseLike($value)
    {
        if (false === strpos($value, '%')) {
            $value = '%'. $value .'%';
        }
        $this->_bind($value);
        return '?';
    }


    /**
     * Generate insert sql
     * @param array $data
     * @return bool
     */
    public function insert(array $data)
    {
        $sql = $this->_grammar->insert($this->_table, $data);
        return $this->_connection->execute($sql, array_values($data));
    }

    /**
     * Generate update sql
     * @param array $data
     * @param mixed $where
     * @return bool
     */
    public function update(array $data, $where = null)
    {
        if ($where) {
            $this->where($where);
        }
        $this->_bind = array_merge(array_values($data), $this->_bind);
        $sql = $this->_grammar->update($this->_table, $data, $this->_where);
        return $this->_connection->execute($sql, $this->getBind());
    }

    /**
     * @param mixed $where
     * @return bool
     */
    public function delete($where = null)
    {
        if ($where) {
            $this->where($where);
        }
        $sql = $this->_grammar->delete($this->_table, $this->_where);
        return $this->_connection->execute($sql, $this->getBind());
    }

    /**
     * @param null $where
     * @return int
     */
    public function count($where = null)
    {
        if ($where) {
            $this->where($where);
        }
        $sql = $this->_grammar->count($this->_table, $this->_where);
        return (int)$this->_connection->fetchOne($sql, $this->getBind());
    }


    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchAll()
    {
        return $this->_connection->fetchAll($this->getSql(), $this->getBind());
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchRow()
    {
        return $this->_connection->fetchRow($this->getSql(), $this->getBind());
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @return array
     */
    public function fetchColumn()
    {
        return $this->_connection->fetchColumn($this->getSql(), $this->getBind());
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @return array
     */
    public function fetchPairs()
    {
        return $this->_connection->fetchPairs($this->getSql(), $this->getBind());
    }

    /**
     * fetch one column value, false returned if nothing or false
     * @return mixed
     */
    public function fetchOne()
    {
        return $this->_connection->fetchOne($this->getSql(), $this->getBind());
    }


    /**
     * @return string
     */
    public function getSql()
    {
        $sql = $this->_grammar->select($this->_selection, $this->_distinct) . $this->_grammar->from($this->_table);
        if ($this->_join) {
            $sql .= $this->_grammar->join($this->_join);
        }
        if ($this->_where) {
            $sql .= ' WHERE ' . trim($this->_where);
        }
        if (!empty($this->_group)) {
            $sql .= $this->_grammar->group($this->_group);
        }
        if (!empty($this->_order)) {
            $sql .= $this->_grammar->orderBy($this->_order);
        }
        if (null !== $this->_limit) {
            $sql .= $this->_grammar->limit($this->_limit, $this->_offset);
        }
        return $sql;
    }

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Bind value
     * @param mixed $bind
     */
    protected function _bind($bind)
    {
        if (is_array($bind)) {
            $this->_bind = array_merge($this->_bind, array_values($bind));
        } else {
            $this->_bind[] = $bind;
        }
    }
}