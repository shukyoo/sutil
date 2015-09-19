<?php namespace Sutil\Database\Adapters;

interface AdapterInterface
{
    /**
     * @return \PDO
     */
    public function connect();

}