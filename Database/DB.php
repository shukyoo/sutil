<?php namespace Sutil\Database;

/**
 * Facade for db query
 * Use this for raw and simple query
 * Recommend use model in normal project
 */
class DB
{
    protected static $_config = [];

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
     * @return ConnectionInterface
     */
    public static function connect($name = null)
    {
        return self::connection($name);
    }

    /**
     * Get a connection
     *
     * @param string $name
     * @return ConnectionInterface
     * @throws \Exception
     */
    public static function connection($name = null)
    {
        static $connections = [];
        $name || $name = '_';
        if (!isset($connections[$name])) {
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
            $connections[$name] = new Connection($config);
        }
        return $connections[$name];
    }

    /**
     * Static call connection method
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::connection(), $method], $args);
    }
}
