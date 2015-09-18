<?php namespace Sutil\Database;

use PDO;

class Query
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_table;
    protected $_sql;
    protected $_bind = [];

    protected $_selection = '*';
    protected $_where = '';
    protected $_group = '';
    protected $_order = [];
    protected $_limit;
    protected $_offset;


    public function __construct(ConnectionInterface $connection, $base = null, $bind = null)
    {
        $this->_connection = $connection;

        // Simplify the init
        // If thers has space in $base then use it as raw sql, otherwise use as table
        if (strpos($base, ' ')) {
            $this->raw($base, $bind);
        } else {
            $this->table($base);
        }
    }

    /**
     * Use single table
     * @param string $table
     * @return $this
     */
    public function table($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * Use raw sql
     * @param string $sql
     * @param mixed $bind
     * @return $this
     */
    public function raw($sql, $bind = null)
    {
        $this->_sql = $sql;
        $this->_addBind($bind);
        return $this;
    }




    /**
     * where clause
     * raw:
     * where('id=2 and name like "%test%"')
     * where('id=? and name like "%?%"', [2, 'test'])
     * where('id in? and name="test"', [1,2,3])
     * where('id in? or name="?"', [[1,2,3], 'test'])
     *
     * @param string $condition
     * @param mixed $bind
     * @return $this
     */
    public function where($condition, $bind = null)
    {
        $this->_where .= " AND {$this->_whereParse($condition, $bind)}";
        return $this;
    }

    /**
     * OR grouped condition
     * @param string $condition
     * @param mixed $bind
     * @return $this
     */
    public function orWhere($condition, $bind = null)
    {
        $this->_where .= " OR ({$this->_whereParse($condition, $bind)})";
        return $this;
    }

    /**
     * AND grouped condition
     * @param string $condition
     * @param mixed $bind
     * @return $this
     */
    public function andWhere($condition, $bind = null)
    {
        $this->_where .= " AND ({$this->_whereParse($condition, $bind)})";
        return $this;
    }

    /**
     * Parse where condition
     * @param string $condition
     * @param mixed $bind
     * @return string
     */
    protected function _whereParse($condition, $bind = null)
    {
        if (strpos($condition, 'in?')) {
            $parts = preg_split('/(\?|in\?)/', $condition, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $str = '';
            foreach ($parts as $item) {
                if ($item == 'in?') {
                    $value = isset($bind[0][0]) ? array_shift($bind) : $bind;
                    $this->_addBind($value);
                    $str .= 'IN('. implode(',', array_fill(0, count($value), '?')) .')';
                } elseif ($item == '?') {
                    $this->_bind[] = array_shift($bind);
                    $str .= $item;
                } else {
                    $str .= $item;
                }
            }
            return $str;
        } else {
            $this->_addBind($bind);
            return $condition;
        }
    }


    /**
     * @param array|string $selection
     */
    public function select($selection)
    {
        if (!is_array($selection)) {
            $this->_selection = $selection;
        } else {
            $this->_selection = implode(',', array_map([$this, '_quoteIdentifier'], $selection));
        }
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
        $this->_group = " GROUP BY {$this->_quoteIdentifier($field)}";
        if ($having) {
            $this->_group .= " HAVING {$having}";
        }
        return $this;
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
     * fetch all array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchAll()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * fetch all with firest field as indexed key, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllIndexed()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllGrouped()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * fetch array of requested class with mapped data, empty array returned if nothing or false
     * @param string|object $class
     * @return array
     */
    public function fetchAllClass($class)
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchRow()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * get instance of the class with mapped data, false returned if nothing or false
     * @param string|object $class
     * @return object|false
     */
    public function fetchRowClass($class)
    {
        return $this->_connection->select($this->getSql(), $this->getBind(), PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @return array
     */
    public function fetchColumn()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @return array
     */
    public function fetchPairs()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     * @return array
     */
    public function fetchPairsGrouped()
    {
        $data = [];
        foreach ($this->_connection->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }

    /**
     * fetch one column value, false returned if nothing or false
     * @return mixed
     */
    public function fetchOne()
    {
        return $this->_connection->select($this->getSql(), $this->getBind())->fetchColumn(0);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        return $this->_connection->execute($this->getSql(), $this->getBind());
    }


    /**
     * Perform insert
     * @param array $data
     * @return bool
     */
    public function insert(array $data)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $sql = 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this->_connection->execute($sql, array_values($data));
    }


    /**
     * Perform update
     * @param array $data
     * @param string $where
     * @param mixex $where_bind
     * @return bool
     */
    public function update(array $data, $where = null, $where_bind = null)
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
        if (null !== $where) {
            $this->where($where, $where_bind);
        }
        $bind = array_merge(array_values($data), $this->_bind);
        $sql = "UPDATE {$this->_table()} SET ". implode(', ', $set) .' WHERE '. ltrim($this->_where, ' AND');
        return $this->_connection->execute($sql, $bind);
    }


    /**
     * Perform delete
     * @param string $where
     * @param mixed $where_bind
     * @return bool
     */
    public function delete($where = null, $where_bind = null)
    {
        $sql = "DELETE FROM {$this->_table()} WHERE ". ltrim($this->_where, ' AND');
        return $this->_connection->execute($sql, $this->_bind);
    }

    /**
     * Update if exists otherwise insert
     * @param array $data
     * @param string $where
     * @param mixed $where_bind
     * @return bool
     */
    public function save($data, $where = null, $where_bind = null)
    {
        if (null !== $where) {
            $this->where($where, $where_bind);
        }
        if ($this->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }


    /**
     * Get count of records
     * @return int
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM '. $this->_table();
        if ($this->_where) {
            $sql .= ' WHERE ' . ltrim($this->_where, ' AND');
        }
        return (int)$this->_connection->select($sql, $this->_bind)->fetchColumn(0);
    }

    /**
     * Check if record exists
     * @return bool
     */
    public function exists()
    {
        return (bool)$this->count();
    }



    /**
     * Get the final sql
     */
    public function getSql()
    {
        $sql = '';
        if (!$this->_sql && $this->_table) {
            $sql = "SELECT {$this->_selection} FROM {$this->_table()}";
        } else {
            $sql = $this->_sql;
        }
        if ($this->_where) {
            if (stripos($sql, ' WHERE ')) {
                $sql .= $this->_where;
            } else {
                $sql .= ' WHERE ' . ltrim($this->_where, ' AND');
            }
        }
        if (!empty($this->_order)) {
            $sql .= ' ORDER BY '. implode(',', $this->_order);
        }
        if (null !== $this->_limit) {
            $sql .= ' LIMIT '. (null === $this->_offset ? $this->_limit : "{$this->_offset},{$this->_limit}");
        }
        return $sql;
    }

    /**
     * Get the bind data
     */
    public function getBind()
    {
        return $this->_bind;
    }


    /**
     * get quoted table
     * @return string
     */
    protected function _table()
    {
        return $this->_quoteIdentifier($this->_table);
    }

    /**
     * @param $identifier
     * @return string
     */
    protected function _quoteIdentifier($identifier)
    {
        $adapter = '\\Sutil\\Database\\Adapters\\'. ucfirst($this->_connection->driver());
        return $adapter::quoteIdentifier($identifier);
    }

    /**
     * Add bind value
     * @param $bind
     */
    protected function _addBind($bind)
    {
        if (null !== $bind) {
            if (!is_array($bind)) {
                $bind = [$bind];
            }
            $this->_bind = array_merge($this->_bind, array_values($bind));
        }
    }
}