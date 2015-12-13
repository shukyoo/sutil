<?php namespace Sutil\Database;

class Grammar
{
    /**
     * @param $table
     * @return string
     */
    public function from($table)
    {
        return ' FROM '. $this->quoteIdent($table);
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
            if (is_array($v) && isset($val[0])) {
                $val = $val[0];
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
        return '`'.str_replace('`', '``', trim($field)).'`';
    }
}