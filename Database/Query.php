<?php namespace Sutil\Database;

use PDO;

class Query
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_sql;
    protected $_bind;


    public function __construct(ConnectionInterface $connection, $sql = null, $bind = null)
    {
        $this->_connection = $connection;
        if ($sql) {
            $this->raw($sql, $bind);
        }
    }

    /**
     * Use raw sql
     * @param string $sql
     * @param mixed $bind
     * @return $this
     */
    public function raw($sql, $bind = null)
    {
        if (strpos($sql, ' in?')) {
            $this->_parseIn($sql, $bind);
        } else {
            $this->_sql = $sql;
            $this->_bind = $bind;
        }
        return $this;
    }

    /**
     * Parse sql if it has in? condition
     */
    protected function _parseIn($sql, $bind)
    {
        $this->_bind = [];
        $parts = preg_split('/(\?|in\?)/', $sql, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $item) {
            if ($item == 'in?') {
                $value = isset($bind[0][0]) ? array_shift($bind) : $bind;
                $this->_bind = array_merge($this->_bind, array_values($value));
                $this->_sql .= 'IN('. implode(',', array_fill(0, count($value), '?')) .')';
            } elseif ($item == '?') {
                $this->_bind[] = array_shift($value);
                $this->_sql .= $item;
            } else {
                $this->_sql .= $item;
            }
        }
    }


    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchAll()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * fetch all with firest field as indexed key, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllIndexed()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllGrouped()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * fetch array of requested class with mapped data, empty array returned if nothing or false
     * @param string|object $class
     * @return array
     */
    public function fetchAllClass($class)
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchRow()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * get instance of the class with mapped data, false returned if nothing or false
     * @param string|object $class
     * @return object|false
     */
    public function fetchRowClass($class)
    {
        return $this->_connection->select($this->_sql, $this->_bind, PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @return array
     */
    public function fetchColumn()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @return array
     */
    public function fetchPairs()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     * @return array
     */
    public function fetchPairsGrouped()
    {
        $data = [];
        foreach ($this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_NUM) as $row) {
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
        return $this->_connection->select($this->_sql, $this->_bind)->fetchColumn(0);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        return $this->_connection->execute($this->_sql, $this->_bind);
    }



    /**
     * @param $table
     * @return QueryBuilder\Builder
     */
    protected function _table($table)
    {
        $driver = $this->_connection->driver();
        $builder = '\\Sutil\\Database\\QueryBuilder\\'. ucfirst($driver) . 'Builder';
        return new $builder($table);
    }


    /**
     * Perform insert
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert($table, array $data)
    {
        $this->_sql = $this->_table($table)->insert($data, $this->_bind);
        return $this->execute();
    }


    /**
     * Perform update
     * @param string $table
     * @param array $data
     * @param array|string $where
     * @return bool
     */
    public function update($table, array $data, $where = null)
    {
        $this->_sql = $this->_table($table)->update($data, $where, $this->_bind);
        return $this->execute();
    }


    /**
     * Perform delete
     * @param string $table
     * @param array|string $where
     * @return bool
     */
    public function delete($table, $where = null)
    {
        $this->_sql = $this->_table($table)->delete($where, $this->_bind);
        return $this->execute();
    }

    /**
     * Update if exists otherwise insert
     * @param string $table
     * @param array $data
     * @param array|string $where
     * @return bool
     */
    public function save($table, $data, $where = null)
    {
        if ($this->exists($table, $where)) {
            return $this->update($table, $data, $where);
        } else {
            return $this->insert($table, $data);
        }
    }

    /**
     * @param string $table
     * @param array|string $where
     * @return int
     */
    public function count($table, $where = null)
    {
        $this->_sql = $this->_table($table)->count($where, $this->_bind);
        return (int)$this->fetchOne();
    }

    /**
     * @param string $table
     * @param array|string $where
     * @return bool
     */
    public function exists($table, $where = null)
    {
        return (bool)$this->count($table, $where);
    }
}