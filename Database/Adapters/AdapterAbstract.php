<?php namespace Sutil\Database\Adapters;

use PDO;

abstract class AdapterAbstract
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
        PDO::ATTR_PERSISTENT => false,
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
        $config_options = empty($this->_config['options']) ? [] : $this->_config['options'];
        return array_diff_key($this->_options, $config_options) + $config_options;
    }
}