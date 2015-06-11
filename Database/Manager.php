<?php namespace Sutil\Database;

class Manager
{


    protected $config = [];
    protected $connections = [];

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function connect($connection = null)
    {

    }

    public function __call($method, $args)
    {

    }
}