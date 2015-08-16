<?php namespace Sutil\Database;

use PDO;

class Query
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_table = '';
    protected $_bind = [];

    protected $_sql = '';
    protected $_where = '';
    protected $_order = [];
    protected $_limit = '';



    public function __construct(ConnectionInterface $connection, $basic = null, $cond = null)
    {
        $this->_connection = $connection;
        $this->based($basic, $cond);
    }

    /**
     * Set base
     * based('table', ['id' => 1])
     * based('select * from table where id=?', 1)
     */
    public function based($basic, $cond = null)
    {
        $basic = trim($basic);
        if (strpos($basic, ' ')) {
            $this->_sql = $basic;
        } else {
            $this->_table = $basic;
        }
        if (!is_array($cond)) {
            $this->_bind = [$cond];
        } elseif (is_numeric(key($cond))) {
            $this->_bind = $cond;
        } else {
            $this->where($cond);
        }
    }

    /**
     * where clause
     * where('user_id=?', 2)
     * where(['user_id=?' => 2, 'user_name=?' => 'test', 'age>=?' => 12])
     * where('user_id in?', [1,2,3])
     * where('user_id notin?', [1,2,3])
     * where('user_id between?', [1, 5])
     * where('user_id is null')
     * where('user_id is not null')
     * where(['id=?' => 1, 'or' => ['id' => 2]])
     * where([['id=?' => 1, 'name=?' => 'test'], ['id=?' => 2, 'name=?' => 'ttt']])
     * where(['id=?' => 1, ['name' => 'test']])
     * where(['id' => 1, 'or id' => 2])
     *
     * @param mixed $cond
     * @param mixed $value
     * @param array &$bind
     * @param array $where_bind [simple mode] elements count should be equal with ? count in $cond
     * @return string
     */
    public function where($cond, $value = null, $co = 'AND')
    {
        $this->_where .= " {$co} " . $this->_whereParse($cond, $value);
        return $this;
    }

    public function orWhere($cond, $value = null)
    {
        return $this->where($cond, $value, 'OR');
    }

    /**
     * Where parse
     */
    protected function _whereParse($cond, $value = null)
    {
        if (is_string($cond)) {
            $cond = trim($cond);
            return (null === $value) ? $cond : $this->_wherePart($cond, $value);
        }
        $where_str = '';
        foreach ($cond as $key => $value) {
            $jc = strtoupper(trim($key));
            if (is_int($key) && is_array($value)) {
                $jc = 'AND';
            }
            if (in_array($jc, ['OR', 'AND'])) {
                $where_str .= " {$jc} ({$this->_whereParse($value)})";
            } else {
                $co = 'AND';
                if (strpos($jc, 'OR ')) {
                    $co = 'OR';
                    $key = str_ireplace('OR ', '', $key);
                }
                $where_str .= " {$co} {$this->_wherePart(trim($key), $value)}";
            }
        }
        return preg_replace('/^(AND|OR)\s+(.*)$/i', '\\2', trim($where_str));
    }

    /**
     * ('id', 1)
     * ('id=?', 1)
     * ('id in?', [1,2])
     * in, notin, between, like, llike, rlike
     */
    protected function _wherePart($cond, $value)
    {
        if (!strpos($cond, '?')) {
            $field = trim($cond);
            $opt = is_array($value) ? 'in' : '=';
        } else {
            preg_match('/^(\w+)(\s+[\w]+|\s*[!=><]+)\s*\?$/', $cond, $matches);
            if (empty($matches[2])) {
                throw new \Exception('Invalid where clause');
            }
            $field = $matches[1];
            $opt = strtolower(trim($matches[2]));
        }
        $field = $this->_connection->quoteIdentifier($field);
        $part_str = $field;
        switch ($opt) {
            case 'in':
                $part_str .= " IN({$this->_inExp($value)})";
                break;
            case 'notin':
                $part_str .= " NOT IN({$this->_inExp($value)})";
                break;
            case 'between':
                $this->_bind = array_merge($this->_bind, $value);
                $part_str .= " BETWEEN ? AND ?";
                break;
            case 'like':
                $this->_bind[] = "%{$value}%";
                $part_str .= ' LIKE ?';
                break;
            case 'llike':
                $this->_bind[] = "%{$value}";
                $part_str .= ' LIKE ?';
                break;
            case 'rlike':
                $this->_bind[] = "{$value}%";
                $part_str .= ' LIKE ?';
                break;
            default:
                $this->_bind[] = $value;
                $part_str .= "{$opt}?";
                break;
        }
        return $part_str;
    }

    protected function _inExp($data)
    {
        if (is_string($data)) {
            $data = explode(',', $data);
        }
        $str = '';
        foreach ($data as $v) {
            $str .= '?,';
            $this->_bind[] = $v;
        }
        return trim($str, ',');
    }


    /**
     * orderBy('id', 'DESC')
     * orderBy(['id' => 'DESC', 'time' => 'ASC'])
     */
    public function orderBy($field, $direction = null)
    {
        if (is_array($field)) {
            foreach ($field as $k=>$v) {
                $this->_order[] = "{$k} {$v}";
            }
        } elseif (null === $direction) {
            $this->_order[] = $field;
        } else {
            $this->_order[] = "{$field} {$direction}";
        }
        return $this;
    }

    public function orderASC($field)
    {
        return $this->orderBy($field, 'ASC');
    }

    public function orderDESC($field)
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Query limit set
     */
    public function limit($number, $offset = 0)
    {
        $this->_limit = (int)$offset .','. (int)$number;
    }

    /**
     * @return array, all array with assoc, empty array returned if nothing or false
     */
    public function fetchAll()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all with firest field as indexed key, empty array returned if nothing or false
     */
    public function fetchAllIndexed()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all grouped array with first field as keys, empty array returned if nothing or false
     */
    public function fetchAllGrouped()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return array, return array of requested class with mapped data, empty array returned if nothing or false
     */
    public function fetchAllClass($class)
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * @return array, one row array with assoc, empty array returned if nothing or false
     */
    public function fetchRow()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return object|false, return instance of the class with mapped data, false returned if nothing or false
     */
    public function fetchRowClass($class)
    {
        return $this->_connection->select($this->_sql(), $this->_bind, PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * @return array return first column array, empty array returned if nothing or false
     */
    public function fetchCol()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * @return array return pairs of first column as Key and second column as Value, empty array returned if nothing or false
     */
    public function fetchPairs()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @return array, return grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     */
    public function fetchPairsGrouped()
    {
        $data = [];
        foreach ($this->_connection->select($this->_sql(), $this->_bind)->fetchAll(PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }

    /**
     * @return mixed, return one column value, false returned if nothing or false
     */
    public function fetchOne()
    {
        return $this->_connection->select($this->_sql(), $this->_bind)->fetchColumn(0);
    }

    /**
     * @return int，count of records
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM '. $this->_table() . $this->_where();
        return $this->_connection->select($sql)->fetchColumn(0);
    }

    /**
     * @return bool，check if exists
     */
    public function exists()
    {
        return (bool)$this->count();
    }

    /**
     * Insert data
     * @param $data
     * @return bool
     */
    public function insert($data)
    {
        is_callable($data) && $data = $data();
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_connection->quoteIdentifier($col);
            $vals[] = '?';
        }
        $sql = 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this->_connection->execute($sql, array_values($data));
    }

    /**
     * Update data
     * @param $data
     * @return bool
     */
    public function update($data)
    {
        is_callable($data) && $data = $data();
        $set = [];
        foreach ($data as $col => $val) {
            if ($val instanceof Expression) {
                $val = $val->getValue();
                unset($data[$col]);
            } else {
                $val = '?';
            }
            $set[] = $this->_connection->quoteIdentifier($col) . ' = ' . $val;
        }
        $this->_bind = array_merge(array_values($data), $this->_bind);
        $sql = "UPDATE {$this->_table()} SET ". implode(', ', $set) . $this->_where();
        return $this->_connection->execute($sql, $this->_bind);
    }

    /**
     * Update if exists otherwise insert
     * @return bool
     */
    public function save($data)
    {
        if ($this->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * Delete data
     * @return bool
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->_table()}{$this->_where()}";
        return $this->_connection->execute($sql, $this->_bind);
    }


    /**
     * Get quoted table
     */
    protected function _table()
    {
        return $this->_connection->quoteIdentifier($this->_table);
    }

    /**
     * Get where
     */
    protected function _where()
    {
        return $this->_where ? (' WHERE '. trim(substr($this->_where, 4))) : '';
    }

    /**
     * Get full sql
     */
    protected function _sql()
    {
        $sql = $this->_sql;

        if ($this->_where) {
            if (stripos($sql, ' where ')) {
                if (stripos($sql, '{where}')) {
                    $sql = str_replace('{where}', $this->_where, $sql);
                } else {
                    $sql .= $this->_where;
                }
            } else {
                $where = trim(substr($this->_where, 4));
                if (stripos($sql, '{where}')) {
                    $sql = str_replace('{where}', " WHERE {$where}", $sql);
                } else {
                    $sql .= " WHERE {$where}";
                }
            }
        }

        if (!empty($this->_order)) {
            $order = implode(',', $this->_order);
            if (stripos($sql, '{order}')) {
                $sql = str_replace('{order}', " ORDER BY {$order}", $sql);
            } else {
                $sql .= " ORDER BY {$order}";
            }
        }

        if ($this->_limit) {
            $sql .= " LIMIT {$this->_limit}";
        }

        return $sql;
    }
}
