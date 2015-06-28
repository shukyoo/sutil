<?php namespace Sutil\Cache;

class Cache
{
    protected static $_config = [];

    public static function config(array $config)
    {
        self::$_config = $config;
    }

    /**
     * @return \Sutil\Cache\Storage\StorageInterface
     */
    protected static function _storage()
    {
        static $storage = null;
        if (null === $storage) {
            $storage_class = '\\Sutil\\Cache\\Storage\\'. ucfirst(strtolower(self::$_config['storage']));
            $storage = new $storage_class(self::$_config['storage_config']);
        }
        return $storage;
    }


    public static function set($key, $value, $expiration = null)
    {
        if (null === $expiration) {
            $expiration = isset(self::$_config['expiration']) ? (int)self::$_config['expiration'] : 0;
        }
        return self::_storage()->set($key, $value, $expiration);
    }


    /**
     * Static call connection method
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::_storage(), $method], $args);
    }


    /**
     * Get the data
     * If date not exists, the data from the callback will be set to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @return mixed
     */
    public static function getData($key, $data_source = null, $expiration = null)
    {
        $key = strtolower($key);
        $data = self::_storage()->get($key);
        if (null === $data) {
            $data = self::setData($key, $data_source, $expiration);
        }
        return $data;
    }

    /**
     * Set the data to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @return mixed
     */
    public static function setData($key, $data_source, $expiration = null)
    {
        is_callable($data_source) && $data_source = $data_source();
        if (!empty($data_source)) {
            $key = strtolower($key);
            self::set($key, $data_source, $expiration);
        }
        return $data_source;
    }

}