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
        if (empty($config['default']) && empty($config['driver'])) {
            throw new \Exception('Invalid database config');
        }
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
     * Get a connection
     *
     * @param string $conn_name
     * @return ConnectionInterface
     * @throws \Exception
     */
    public static function connect($conn_name = null)
    {
        static $connections = [];

        if (!empty($conn_name)) {
            $name = $conn_name;
        } else {
            $name = !empty(self::$_config['default']) ? self::$_config['default'] : self::$_config['driver'];
        }

        if (!isset($connections[$name])) {
            $index = $conn_name ?: self::$_config['default'];
            if ($index) {
                if (empty(self::$_config[$index])) {
                    throw new \Exception('Invalid connection name in database config');
                }
                $config = self::$_config[$index];
            } else {
                $config = self::$_config;
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
        return call_user_func_array([self::connect(), $method], $args);
    }


    /**
     * For query
     * @param string $base
     * @param mixed $bind
     * @return Query
     */
    public static function query($base, $bind = null)
    {
        return self::connect()->query($base, $bind);
    }
}
