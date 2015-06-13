<?php namespace Sutil\Database;

/**
 * Facade for db query
 * Use this for raw and simple query
 * Recommend use model in normal project
 *
 * Auto detect master for write and slave for read
 * Config examples:
 * array(
 *     'driver' => 'mysql',
 *     'host' => '',
 *     'dbname' => '',
 *     'username' => '',
 *     'password' => '',
 *     'charset' => 'utf8',
 *     'timezone' => '',
 *     'options' => [],
 *     'slaves' => []
 * )
 * OR multi database
 * array(
 *     'mysql' => array(
 *         'driver' => 'mysql',
 *         'slaves' => array(
 *             array(
 *                 'host' => '',
 *                 'dbname' => '',
 *             )
 *         )
 *     ),
 *     'other_db2' => array(
 *         'driver' => 'mysql',
 *         'slave' => array(
 *             'host' => ''
 *         )
 *     ),
 *     'other3' => array(
 *         'masters' => [],
 *         'slaves' => []
 *     )
 * )
 */
class DB
{
    protected static $_config = [];
    protected static $_connections = [];
    protected static $_queries = [];

    public static function config(Array $config)
    {
        self::$_config = $config;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function getConfig($name = null)
    {
        return isset($name) ? self::$_config[$name] : self::$_config;
    }

    /**
     * Connect specified database
     *
     * @param string $name
     * @return Query
     */
    public static function connect($name)
    {
        return self::getQuery($name);
    }

    /**
     * Get a connection
     *
     * @param string $name
     * @return ConnectionInterface
     * @throws \Exception
     */
    public static function getConnection($name = null)
    {
        $name || $name = '_';
        if (!isset(self::$_connections[$name])) {
            if ($name != '_') {
                if (empty(self::$_config[$name])) {
                    throw new \Exception('Invalid connection name');
                }
                $config = self::$_config[$name];
            } elseif (!empty(self::$_config['driver'])) {
                $config = self::$_config;
            } else {
                $config = array_values(self::$_config)[0];
            }
            self::$_connections[$name] = new Connection($config);
        }
        return self::$_connections[$name];
    }


    /**
     * @param null|string $name
     * @return QueryInterface
     */
    public static function getQuery($name = null)
    {
        $name || $name = '_';
        if (!isset(self::$_queries[$name])) {
            self::$_queries[$name] = new Query(self::getConnection($name));
        }
        return self::$_queries[$name];
    }


    /**
     * Static call query method
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::getQuery(), $method], $args);
    }
}
