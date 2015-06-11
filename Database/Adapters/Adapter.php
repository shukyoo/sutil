<?php namespace Sutil\Database;

use PDO;

abstract class Adapter
{
    /**
     * The default PDO connection options.
     */
    protected $_options = array(
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    );

    protected $_config = [];

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    public function getOptions()
    {
        return array_merge($this->_options, $this->_config['options']);
    }
}