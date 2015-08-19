<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;

class Table implements AssembleInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_table;

    protected $_selection = '*';


    public function __construct(ConnectionInterface $connection, $table, $where = null)
    {
        $this->_adapter = $adapter;
        $this->_table = trim($table);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * @param string|array $selection
     */
    public function select($selection)
    {
        if (is_string($selection)) {
            $selection = explode(',', $selection);
        }
        foreach ($selection as $field) {

        }
    }


    /**
     * {@inheritDoc}
     */
    public function getSql()
    {

    }


    /**
     * {@inheritDoc}
     */
    public function getBind()
    {

    }

    public function getQuerier()
    {
        return new Querier($this);
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
}