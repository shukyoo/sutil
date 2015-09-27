<?php namespace Sutil\Database\Query;

use Sutil\Database\Connection;

class Sql extends QueryAbstract
{
    protected $_sql;
    protected $_bind = [];

    public function __construct(Connection $connection, $sql, $bind = null)
    {
        $this->_connection = $connection;

        $this->_sql = $sql;

        if (null !== $bind) {
            $this->bind($bind);
        }
    }

    /**
     * Append sql
     * @param string $sql
     * @param mixed $bind
     */
    public function append($sql, $bind = null)
    {
        $this->_sql .= ' '. trim($sql);
        if (null !== $bind) {
            $this->bind($bind);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getSql()
    {
        return $this->_sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Bind value
     */
    public function bind($bind)
    {
        if (is_array($bind)) {
            $this->_bind = array_merge($this->_bind, $bind);
        } else {
            $this->_bind[] = $bind;
        }
    }
}