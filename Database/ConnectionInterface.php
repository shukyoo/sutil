<?php namespace Sutil\Database;

interface ConnectionInterface
{
    /**
     * Prepare statement
     * Auto detect "select" sql, for use slave db
     *
     * @param string $sql
     * @return \PDOStatement
     */
    public function prepare($sql);

    /**
     * @return \PDO the master one
     */
    public function getPDO();

    /**
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier);
}