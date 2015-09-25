<?php namespace Sutil\Database\Query\Grammars;

abstract class GrammarAbstract
{

    /**
     * @param array|string $selection
     * @return string
     */
    public function select($selection)
    {
        return is_array($selection) ? implode(',', array_map([$this, '_quoteIdentifier'], $selection)) : $selection;
    }

    /**
     * Group part
     * @param string $field
     * @param string $having
     * @return string
     */
    public function group($field, $having = null)
    {
        $having = $having ? " HAVING {$having}" : '';
        return " GROUP BY {$this->_quoteIdentifier($field)}{$having}";
    }

    /**
     * orderBy('id DESC')
     * @param string $field
     * @return $this
     */
    public function orderBy($order)
    {
        $this->_order[] = $order;
        return $this;
    }
    /**
     * @param string $field
     * @return $this
     */
    public function orderASC($field)
    {
        return $this->orderBy("{$this->_quoteIdentifier($field)} ASC");
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderDESC($field)
    {
        return $this->orderBy("{$this->_quoteIdentifier($field)} DESC");
    }


    /**
     * Generate insert sql
     * @param array $data
     * @param mixed $bind
     * @return string
     */
    public function insert(array $data, &$bind = null)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $bind = array_values($data);
        return 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
    }

    /**
     * Generate update sql
     * @param array $data
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function update(array $data, $where = null, &$bind = null)
    {
        $set = [];
        foreach ($data as $col => $val) {
            if (is_array($val) && isset($val[0])) {
                $val = $val[0];
                unset($data[$col]);
            } else {
                $val = '?';
            }
            $set[] = $this->_quoteIdentifier($col) . '=' . $val;
        }
        $bind = array_values($data);
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return "UPDATE {$this->_table()} SET ". implode(', ', $set) . $where_str;
    }

    /**
     * Generate delete sql
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function delete($where = null, &$bind = null)
    {
        $bind = [];
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return "DELETE FROM {$this->_table()}". $where_str;
    }

    /**
     * Get count sql
     * @return string
     */
    public function count($where = null, &$bind = null)
    {
        $bind = [];
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return 'SELECT COUNT(*) FROM '. $this->_table() . $where_str;
    }


    /**
     * where clause
     * where('id=2 and name like "%test%"')
     * where(['id=? and name like "%?%"' => [2, 'test'])
     * where(['id in? and name="test"' => [1,2,3]])
     * where(['id in? or name="?"' => [[1,2,3], 'test']])
     *
     * where(['id' => 1, 'name like ?' => 'test'])
     * where(['id' => [1,2,3], 'or' => ['xxxxxx']])
     *
     * @param string $condition
     * @param mixed $bind
     * @return $this
     */
    protected function _where($where, &$bind = null)
    {
        if (!is_array($where)) {
            return $where;
        }
        $where_str = '';
        foreach ($where as $k => $v) {
            $jc = is_int($k) ? 'AND' : strtoupper(trim($k));
            if ($jc == 'AND' || $jc == 'OR') {
                $co = $where_str ? " {$jc} " : '';
                $where_str .= $co . $this->_where($v, $bind);
            } else {
                $co = $where_str ? " AND " : '';
                $where_str .= $co . $this->_wherePart($k, $v, $bind);
            }
        }
        return $where_str;
    }


    /**
     * (id=? and name=?, [1, 'test'])
     * ('id', 1)
     * ('id', [1,2,3])
     */
    protected function _wherePart($condition, $value, &$bind = null)
    {
        if (!strpos($condition, '?')) {
            if (is_array($value)) {
                return $this->_quoteIdentifier($condition) .' IN('. $this->_whereIn($value, $bind) .')';
            } else {
                $this->_addBind($bind, $value);
                return $this->_quoteIdentifier($condition) .'=?';
            }
        } elseif (strpos($condition, 'in?')) {
            $parts = preg_split('/(\?|in\?)/', $condition, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $str = '';
            foreach ($parts as $item) {
                if ($item == 'in?') {
                    $str .= 'IN('. $this->_whereIn((isset($value[0][0]) ? array_shift($value) : $value), $bind) .')';
                } elseif ($item == '?') {
                    $this->_addBind($bind, array_shift($value));
                    $str .= $item;
                } else {
                    $str .= $item;
                }
            }
            return $str;
        } else {
            $this->_addBind($bind, $value);
            return $condition;
        }
    }


    protected function _whereIn($value, &$bind = null)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $this->_addBind($bind, $value);
        return implode(',', array_fill(0, count($value), '?'));
    }


    protected function _addBind(&$bind, $value)
    {
        if (null == $bind) {
            $bind = [];
        } elseif (!is_array($bind)) {
            $bind = [$bind];
        }
        if (is_array($value)) {
            $bind = array_merge($bind, array_values($value));
        } else {
            $bind[] = $value;
        }
    }



    /**
     * get quoted table
     * @return string
     */
    protected function _table()
    {
        return $this->_wrap($this->_table);
    }



    /**
     * Wrap a column identifiers.
     * @param string $field
     * @return string
     */
    protected function _wrap($field)
    {
        return '"'.str_replace('"', '""', $field).'"';
    }
}