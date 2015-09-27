<?php namespace Sutil\Cache;

class Cache
{
    protected static $_config = [];

    public static function config(array $config)
    {
        if (empty($config['default']) && empty($config['driver'])) {
            throw new \Exception('Invalid cache config');
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
     * @param string $storage_name
     * @return Backend
     * @throws \Exception
     */
    public static function backend($storage_name = null)
    {
        static $backends = [];

        if (!empty($storage_name)) {
            $name = $storage_name;
        } else {
            $name = !empty(self::$_config['default']) ? self::$_config['default'] : self::$_config['driver'];
        }
        if (!isset($backends[$name])) {
            if ($storage_name || !empty(self::$_config['default'])) {
                $index = $storage_name ?: self::$_config['default'];
                if (empty(self::$_config[$index])) {
                    throw new \Exception('Invalid storage name in cache config');
                }
                $config = self::$_config[$index];
            } else {
                $config = self::$_config;
            }
            $backends[$name] = new Backend($config);
        }

        return $backends[$name];
    }


    /**
     * Static call connection method
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::backend(), $method], $args);
    }
}