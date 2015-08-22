<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;

abstract class QueryAbstract
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Get final SQL string
     * @return mixed
     */
    abstract public function getSql();

    /**
     * Get final bind
     * @return array
     */
    abstract public function getBind();
    
    /**
     * @return array, all array with assoc, empty array returned if nothing or false
     */
    public function fetchAll()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all with firest field as indexed key, empty array returned if nothing or false
     */
    public function fetchAllIndexed()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * @return array, fetch all grouped array with first field as keys, empty array returned if nothing or false
     */
    public function fetchAllGrouped()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return array, return array of requested class with mapped data, empty array returned if nothing or false
     */
    public function fetchAllClass($class)
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * @return array, one row array with assoc, empty array returned if nothing or false
     */
    public function fetchRow()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string|object $class
     * @return object|false, return instance of the class with mapped data, false returned if nothing or false
     */
    public function fetchRowClass($class)
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind(), PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * @return array return first column array, empty array returned if nothing or false
     */
    public function fetchCol()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * @return array return pairs of first column as Key and second column as Value, empty array returned if nothing or false
     */
    public function fetchPairs()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @return array, return grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     */
    public function fetchPairsGrouped()
    {
        $data = [];
        foreach ($this->getConnection()->select($this->getSql(), $this->getBind())->fetchAll(PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }

    /**
     * @return mixed, return one column value, false returned if nothing or false
     */
    public function fetchOne()
    {
        return $this->getConnection()->select($this->getSql(), $this->getBind())->fetchColumn(0);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        return $this->getConnection()->execute($this->getSql(), $this->getBind());
    }
}