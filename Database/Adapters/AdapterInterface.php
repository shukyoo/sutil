<?php namespace Sutil\Database\Adapters;

interface AdapterInterface
{
    /**
     * @return \PDO
     */
    public function connect();

    /**
     * Quote identifier
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier);
}