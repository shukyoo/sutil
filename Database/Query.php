<?php namespace Sutil\Database;

use PDO;
use Sutil\Database\QueryBuilders\BuilderInterface;

class Query
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    /**
     * @var BuilderInterface
     */
    protected $_builder;

    /**
     * Raw sql for simple and quick process
     */
    protected $_sql;
    protected $_bind;


    public function __construct(ConnectionInterface $connection, $base = null, $bind = null)
    {
        $this->_connection = $connection;

        // Simplify the init
        // If no space in $base then use it as table(recommend no space in your tablename)
        // If there has space in your tablename, you should use table($table_name) method
        if (strpos($base, ' ')) {
            $this->sql($base, $bind);
        } else {
            $this->table($base);
        }
    }

    /**
     * Use table builder
     */
    public function table($table_name)
    {
        $this->_builder = new QueryBuilders\Table($this->_connection, $table_name);
        return $this;
    }

    /**
     * Use sql builder
     */
    public function sql($sql, $bind = null)
    {
        // # means IN clause
        // { means variable assignment
        if (strpos($sql, '{') || strpos($sql, '#')) {
            $this->_builder = new QueryBuilders\Sql($sql, $bind);
        } else {
            $this->raw($sql, $bind);
        }
        return $this;
    }

    /**
     * Raw sql
     */
    public function raw($sql, $bind = null)
    {
        $this->_sql = $sql;
        $this->_bind = $bind;
        return $this;
    }

    /**
     * Call builder method
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->_builder, $method], $args);
        return $this;
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
    public function fetchCol()
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
     * Get the final sql
     */
    public function getSql()
    {
        if ($this->_sql) {
            return $this->_sql;
        }
        return $this->_builder->getSql();
    }

    /**
     * Get the bind data
     */
    public function getBind()
    {
        if ($this->_sql) {
            return $this->_bind;
        }
        return $this->_builder->getBind();
    }
}