<?php namespace Sutil\Database\Query\Grammars;

class GrammarBase
{
    /**
     * @param $table
     * @return string
     */
    public function from($table)
    {
        return "FROM {$this->wrap($table)}";
    }

    /**
     * @param array|string $selection
     * @return string
     */
    public function select($selection, $distinct = false)
    {
        $distinct = $distinct ? 'DISTINCT ' : '';
        return 'SELECT ' . $distinct . (is_array($selection) ? implode(',', array_map([$this, 'wrap'], $selection)) : $selection);
    }

    /**
     * @param array $group
     * @return string
     */
    public function group($group)
    {
        $having = !empty($group[1]) ? " HAVING {$group[1]}" : '';
        return "GROUP BY {$this->wrap($group[0])}{$having}";
    }

    /**
     * @param array $order
     * @param string $direction
     * @return $this
     */
    public function orderBy($order)
    {
        $order_arr = [];
        foreach ($order as $k => $v) {
            if (is_int($k)) {
                $order_arr[] = $v;
            } else {
                $order_arr[] = "{$k} {$v}";
            }
        }
        return 'ORDER BY '. implode(',', $order_arr);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function limit($limit, $offset = null)
    {
        return "LIMIT {$limit}" . ($offset ? " OFFSET {$offset}" : '');
    }


    /**
     * Generate insert sql
     * @param array $data
     * @param mixed $bind
     * @return string
     */
    public function insert($table, array $data, &$bind = null)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->wrap($col);
            $vals[] = '?';
        }
        $bind = array_values($data);
        return 'INSERT INTO ' . $this->wrap($table) .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
    }

    /**
     * Generate update sql
     * @param array $data
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function update($table, array $data, $where = '', &$bind = null)
    {
        $set = [];
        foreach ($data as $col => $val) {
            if (is_array($val) && isset($val[0])) {
                $val = $val[0];
                unset($data[$col]);
            } else {
                $val = '?';
            }
            $set[] = $this->wrap($col) . '=' . $val;
        }
        $bind = array_values($data);
        return "UPDATE {$this->wrap($table)} SET ". implode(', ', $set) . $this->_where($where);
    }

    /**
     * Generate delete sql
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function delete($table, $where = '')
    {
        return "DELETE {$this->from($table)}". $this->_where($where);
    }

    /**
     * Get count sql
     * @return string
     */
    public function count($table, $where = '')
    {
        return 'SELECT COUNT(*) '. $this->from($table) . $this->_where($where);
    }

    /**
     * @param string $where
     * @return string
     */
    protected function _where($where)
    {
        $where = trim($where);
        return $where ? " WHERE {$where}" : '';
    }


    /**
     * Wrap a column identifiers.
     * @param string $field
     * @return string
     */
    public function wrap($field)
    {
        return '"'.str_replace('"', '""', $field).'"';
    }
}