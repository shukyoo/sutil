<?php namespace Sutil\Database;

class Grammar
{
    /**
     * @param $table
     * @return string
     */
    public function from($table)
    {
        if (is_array($table) && !empty($table[1])) {
            return ' FROM '. $this->quoteIdent($table[0]) .' AS '. $table[1];
        } else {
            return ' FROM '. $this->quoteIdent($table);
        }
    }

    /**
     * @param $join
     * @return string
     */
    public function join($join)
    {
        $str = '';
        foreach ($join as $item) {
            if (is_array($item['table']) && !empty($item['table'][1])) {
                $table = $this->quoteIdent($item['table'][0]) .' AS '. $item['table'][1];
            } else {
                $table = $this->quoteIdent($item['table']);
            }
            $str .= ' '. $item['type'] .' '. $table .' ON '. $item['on'];
        }
        return $str;
    }

    /**
     * @param array $selection
     * @return string
     */
    public function select($selection, $distinct = false)
    {
        $distinct = $distinct ? ' DISTINCT' : '';
        $str = 'SELECT'. $distinct .' ';
        foreach ($selection as $item) {
            if (is_array($item)) {
                $str .= array_shift($item) .',';
            } else {
                $str .= $this->quoteIdent($item) .',';
            }
        }
        return substr($str, 0, -1);
    }

    /**
     * @param array $group
     * @return string
     */
    public function group($group)
    {
        $having = empty($group[1]) ? '' : " HAVING {$group[1]}";
        return ' GROUP BY'. $this->quoteIdent($group[0]) . $having;
    }

    /**
     * @param array $order
     * @return string
     */
    public function orderBy($order)
    {
        $order_arr = [];
        foreach ($order as $k => $v) {
            $order_arr[] = $this->quoteIdent($k) .' '. $v;
        }
        return ' ORDER BY '. implode(',', $order_arr);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function limit($limit, $offset = null)
    {
        return ' limit '. $limit . ($offset ? " OFFSET {$offset}" : '');
    }
    
    /**
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert($table, array $data)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $cols[] = $this->quoteIdent($k);
            $vals[] = '?';
        }
        return 'INSERT INTO ' . $this->quoteIdent($table) .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
    }

    /**
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @return bool
     */
    public function update($table, array $data, $where = null)
    {
        $sets = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v[0])) {
                $val = $v[0];
                unset($data[$k]);
            } else {
                $val = '?';
            }
            $sets[] = $this->quoteIdent($k) . '=' . $val;
        }
        $where = $where ? (' WHERE '. $where) : '';
        return 'UPDATE '. $this->quoteIdent($table) .' SET '. implode(',', $sets) . $where;
    }

    /**
     * @param $table
     * @param mixed $where
     * @return bool
     */
    public function delete($table, $where = null)
    {
        $where = $where ? (' WHERE '. $where) : '';
        return 'DELETE FROM '. $this->quoteIdent($table) . $where;
    }


    /**
     * @param $field
     * @return string
     */
    public function quoteIdent($field)
    {
        $poz = strpos($field, '.');
        if ($poz) {
            return ($this->_quote(substr($field, 0, $poz)) .'.'. $this->_quote(substr($field, $poz+1)));
        } else {
            return $this->_quote($field);
        }
    }

    /**
     * @param $field
     * @return string
     */
    protected function _quote($field)
    {
        return '`'.str_replace('`', '``', trim($field)).'`';
    }
}