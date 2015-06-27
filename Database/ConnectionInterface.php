<?php namespace Sutil\Database;

interface ConnectionInterface
{
    /**
     * Get a master PDO instance
     * @return \PDO
     */
    public function master();

    /**
     * Get a slave PDO instance
     * @return \PDO
     */
    public function slave();
    

    /**
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier);
}