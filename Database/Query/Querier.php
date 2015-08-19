<?php namespace Sutil\Database\Query;

use PDO;
use Sutil\Database\ConnectionInterface;

class Querier
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;
    protected $_sql;
    protected $_bind;

    public function __construct(BuilderInterface $builder)
    {
        $this->_connection = $builder->getConnection();
        $this->_sql = $builder->getSql();
        $this->_bind = $builder->getBind();
    }

    /**
     * @return array, all array with assoc, empty array returned if nothing or false
     */
    public function fetchAll()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all with firest field as indexed key, empty array returned if nothing or false
     */
    public function fetchAllIndexed()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all grouped array with first field as keys, empty array returned if nothing or false
     */
    public function fetchAllGrouped()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return array, return array of requested class with mapped data, empty array returned if nothing or false
     */
    public function fetchAllClass($class)
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * @return array, one row array with assoc, empty array returned if nothing or false
     */
    public function fetchRow()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return object|false, return instance of the class with mapped data, false returned if nothing or false
     */
    public function fetchRowClass($class)
    {
        return $this->_connection->select($this->_sql, $this->_bind, PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * @return array return first column array, empty array returned if nothing or false
     */
    public function fetchCol()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * @return array return pairs of first column as Key and second column as Value, empty array returned if nothing or false
     */
    public function fetchPairs()
    {
        return $this->_connection->select($this->_sql, $this->_bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @return array, return grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
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
     * @return mixed, return one column value, false returned if nothing or false
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
}
