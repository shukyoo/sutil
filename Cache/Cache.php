<?php namespace Sutil\Cache;

class Cache
{
    protected static $_config = [];

    public static function config(array $config)
    {
        if (empty($config['default'])) {
            throw new \Exception('Lost "default" set in your cache config');
        }
        self::$_config = $config;
    }


    public static function backend($storage_name = null)
    {
        static $backends = [];
        $storage_name || $storage_name = self::$_config['default'];
        if (!isset($queries[$storage_name])) {
            if (empty(self::$_config[$storage_name])) {
                throw new \Exception('Invalid cache config');
            }
            $storage_class = '\\Sutil\\Cache\\Storage\\'. ucfirst(strtolower($storage_name));
            $backend_config = [];
            if (isset(self::$_config['expiration'])) {
                $backend_config['expiration'] = self::$_config['expiration'];
            }
            $backends[$storage_name] = new Backend(new $storage_class(self::$_config[$storage_name]), $backend_config);
        }
        return $backends[$storage_name];
    }

    /**
     * Static call connection method
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::backend(), $method], $args);
    }
}