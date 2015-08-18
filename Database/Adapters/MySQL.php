<?php namespace Sutil\Database\Adapters;

use PDO;

/**
 * Config example:
 * array(
 *     'host' => 'localhost', | 'unix_socket' => 'xxxx', // optional default 127.0.0.1
 *     'port' => '3306', // optional default port
 *     'dbname' => 'test', // * required
 *     'username' => 'user', //optional default null
 *     'password' => '123', //optional default null
 *     'charset' => 'utf8', // optional default utf8
 *     'options' => [], // optional
 * )
 */
class Mysql extends AdapterAbstract implements AdapterInterface
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_CHARSET = 'utf8';

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        // dsn
        if (empty($this->_config['unix_socket'])) {
            $host = empty($this->_config['host']) ? self::DEFAULT_HOST : $this->_config['host'];
            $port = empty($this->_config['port']) ? '' : ";port={$this->_config['port']}";
            $link = "host={$host}{$port}";
        } else {
            $link = "unix_socket={$this->_config['unix_socket']}";
        }
        $dbname = empty($this->_config['dbname']) ? '' : $this->_config['dbname'];
        $dsn = "mysql:{$link};dbname={$dbname}";

        // options
        $charset = empty($this->_config['charset']) ? self::DEFAULT_CHARSET : $this->_config['charset'];
        $options = $this->getOptions();
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$charset}'";

        // user
        $username = empty($this->_config['username']) ? null : $this->_config['username'];
        $password = empty($this->_config['password']) ? null : $this->_config['password'];

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifier($identifier)
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}