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

    /**
     * Common part: limit
     */
    protected $_limit = '';


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
     * Set limit clause by page for common simplify usage
     * @param int $page
     * @param int $page_size default 20
     * @return $this
     */
    public function forPage($page, $page_size = 20)
    {
        $this->_limit = ' LIMIT '. ($page - 1) * $page_size .','. $page_size;
        return $this;
    }



    /**
     * Prepare insert query before execute
     * @param array $data
     * @return $this
     */
    public function prepareInsert(array $data)
    {
        if (!$this->_builder instanceof QueryBuilders\Table) {
            throw new \Exception('should be table builder');
        }
        $this->_builder->insert($data);
        return $this;
    }

    /**
     * Prepare update query before execute
     * @param array $data
     * @param mixed $where
     */
    public function prepareUpdate(array $data, $where = null)
    {
        if (!$this->_builder instanceof QueryBuilders\Table) {
            throw new \Exception('should be table builder');
        }
        $this->_builder->update($data, $where);
        return $this;
    }

    /**
     * Prepare delete query before execute
     * @param array|string $where
     * @return $this
     */
    public function prepareDelete($where = null)
    {
        if (!$this->_builder instanceof QueryBuilders\Table) {
            throw new \Exception('should be table builder');
        }
        $this->_builder->delete($where);
        return $this;
    }


    /**
     * Execute insert
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function insert(array $data)
    {
        return $this->prepareInsert($data)->execute();
    }

    /**
     * Execute update
     * @param array $data
     * @param array|string $where
     * @return bool
     * @throws \Exception
     */
    public function update(array $data, $where = null)
    {
        return $this->prepareUpdate($data, $where)->execute();
    }

    /**
     * Execute delete
     * @param array|string $where
     * @return bool
     * @throws \Exception
     */
    public function delete($where = null)
    {
        return $this->prepareDelete($where)->execute();
    }

    /**
     * @param array|string $where
     * @return int
     * @throws \Exception
     */
    public function count($where = null)
    {
        if (!$this->_builder instanceof QueryBuilders\Table) {
            throw new \Exception('should be table builder');
        }
        $this->_builder->count($where);
        return $this->fetchOne();
    }

    /**
     * Execute save
     * perform update if exists otherwise perform insert
     *
     */
    public function save($data, $where = null)
    {
        if (!$this->_builder instanceof QueryBuilders\Table) {
            throw new \Exception('should be table builder');
        }
        if (null !== $where) {
            $this->_builder->where($where);
        }
        if ((int)$this->count() > 0) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
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
    protected function getSql()
    {
        if ($this->_sql) {
            return $this->_sql . $this->_limit;
        }
        return ($this->_builder->getSql() . $this->_limit);
    }

    /**
     * Get the bind data
     */
    protected function getBind()
    {
        if ($this->_sql) {
            return $this->_bind;
        }
        return $this->_builder->getBind();
    }
}