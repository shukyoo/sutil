<?php namespace Sutil\Config;

class BasicArray
{
    protected static $_pool = [];

    /**
     * Load data
     * @param array $val
     */
    public static function load(array $pool)
    {
        self::$_pool = $pool;
    }

    /**
     * @param string $key use dot to find the deep value
     * @param mixed $default
     */
    public static function get($key, $default = null)
    {
        return isset(self::$_pool[$key]) ? self::$_pool[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::$_pool[$key] = $value;
    }

    /**
     * Check if exists
     * @param string $key
     */
    public static function has($key)
    {
        return isset(self::$_pool[$key]);
    }

    /**
     * Get all data
     */
    public static function all()
    {
        return self::$_pool;
    }
}