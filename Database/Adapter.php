<?php namespace Sutil\Database;

use PDO;

/**
 * Config example:
 * array(
 *     'dsn' => 'mysql:dbname=testdb;host=127.0.0.1;port=3306'
 *     'username' => 'user', //optional default null
 *     'password' => '123', //optional default null
 *     'options' => [], // optional
 * )
 */
class Adapter
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


    /**
     * setup and get PDO instance
     * @return PDO
     */
    public function connect()
    {
        if (empty($this->_config['dsn'])) {
            throw new \Exception('dsn config is required for database');
        }

        $username = empty($this->_config['username']) ? null : $this->_config['username'];
        $password = empty($this->_config['password']) ? null : $this->_config['password'];

        return new PDO($this->_config['dsn'], $username, $password, $this->getOptions());
    }
}