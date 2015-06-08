<?php namespace Sutil\Database;

/**
 * Facade for db query
 * Use this for raw and simple query
 * Recommend use model in normal project
 */
class DB
{
    protected static $config = [];

    public static function config(Array $config)
    {
        self::$config = $config;
    }

    public static function getConfig()
    {
        return self::$config;
    }

    public static function getManager()
    {
        static $manager = null;
        if (null === $manager) {
            $manager = new Manager(self::$config);
        }
        return $manager;
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::getManager(), $method), $args);
    }
}
