<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;

class Table extends QueryAbstract
{
    protected $_grammar;
    protected $_table;

    protected $_selection = '*';
    protected $_where = '';
    protected $_group = '';
    protected $_order = [];
    protected $_limit;
    protected $_offset;

    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->_connection = $connection;
        $this->_table = trim($table);

        $driver = $connection->driver();
        $grammar = 'Grammars\\'. ucfirst($driver);
        if (!class_exists($grammar)) {
            throw new \Exception('Grammar of '. $driver .' has not been implemented');
        }
        $this->_grammar = new $grammar($this);
    }



    /**
     * @param array|string $selection
     * @return string
     */
    public function select($selection)
    {
        return is_array($selection) ? implode(',', array_map([$this, '_quoteIdentifier'], $selection)) : $selection;
    }

    /**
     * Group part
     * @param string $field
     * @param string $having
     * @return string
     */
    public function group($field, $having = null)
    {
        $having = $having ? " HAVING {$having}" : '';
        return " GROUP BY {$this->_quoteIdentifier($field)}{$having}";
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
     * Generate insert sql
     * @param array $data
     * @param mixed $bind
     * @return string
     */
    public function insert(array $data, &$bind = null)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $bind = array_values($data);
        return 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
    }

    /**
     * Generate update sql
     * @param array $data
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function update(array $data, $where = null, &$bind = null)
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
        $bind = array_values($data);
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return "UPDATE {$this->_table()} SET ". implode(', ', $set) . $where_str;
    }

    /**
     * Generate delete sql
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function delete($where = null, &$bind = null)
    {
        $bind = [];
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return "DELETE FROM {$this->_table()}". $where_str;
    }

    /**
     * Get count sql
     * @return string
     */
    public function count($where = null, &$bind = null)
    {
        $bind = [];
        $where_str = '';
        if (null !== $where) {
            $where_str = ' WHERE '. $this->_where($where, $bind);
        }
        return 'SELECT COUNT(*) FROM '. $this->_table() . $where_str;
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
}