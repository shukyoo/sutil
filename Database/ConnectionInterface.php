<?php namespace Sutil\Database;

interface ConnectionInterface
{
    /**
     * @param string $sql
     * @return \PDOStatment
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