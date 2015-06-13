<?php namespace Sutil\Database;

/**
 * Facade for db query
 * Use this for raw and simple query
 * Recommend use model in normal project
 * Config examples:
 * array(
 *     'host' => '',
 *     'dbname' => '',
 *     'slaves' => []
 * )
 * OR multi database
 * array(
 *     'mysql' => array(
 *         'host' => '',
 *         'dbname' => '',
 *         'slaves' => array(
 *             array(
 *                 'host' => '',
 *                 'dbname' => '',
 *             )
 *         )
 *     ),
 *     'other_db2' => array(
 *         'host' => '',
 *         'slave' => array(
 *             'host' => ''
 *         )
 *     )
 * )
 */
class DB
{
    protected static $_config = [];
    protected static $_queries = [];

    public static function config(Array $config)
    {
        self::$_config = $config;
    }

    public static function getConfig($name = null)
    {
        return isset($name) ? self::$_config[$name] : self::$_config;
    }

    /**
     * @param string $name
     * @return Query
     */
    public static function connect($name)
    {
        return self::getQuery($name);
    }

    /**
     * @param null|string $name
     * @return Query
     */
    public static function getQuery($name = null)
    {
        $name || $name = '_';
        if (!isset(self::$_queries[$name])) {
            if ($name != '_') {
                $config = self::getConfig($name);
            } elseif (!empty(self::$_config['dbname'])) {
                $config = self::$_config;
            } else {
                $config = array_values(self::$_config)[0];
            }
            self::$_queries[$name] = new Query($config);
        }
        return self::$_queries[$name];
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::getQuery(), $method], $args);
    }
}
