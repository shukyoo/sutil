<?php namespace Sutil\Datamap;

use Sutil\Cache\Cache;
use Sutil\Database\DB;

abstract class Datamap
{
    /**
     * Method set here will be auto set to cache
     * Key for method name, value for expiration
     * Example:
     * array(
     *     'getList' => 1200,
     *     'getSomething' => 0
     * )
     */
    protected $_cache_methods = [];

    /**
     * Specify the connection of the database
     */
    protected $_connection = null;

    /**
     * Specify the storage of the cache(memcache, redis etc.)
     */
    protected $_cache_storage = null;

    /**
     * @var \Sutil\Database\QueryInterface
     */
    protected $_db = null;

    /**
     * @var \Sutil\Cache\BackendInterface
     */
    protected $_cache = null;

    /**
     * static instance pool
     */
    protected static $_instances = [];


    /**
     * @return \Sutil\Database\QueryInterface
     */
    public function db()
    {
        if (null === $this->_db) {
            $this->_db = DB::query($this->_connection);
        }
        return $this->_db;
    }

    /**
     * @return \Sutil\Cache\BackendInterface
     * @throws \Exception
     */
    public function cache()
    {
        if (null === $this->_cache) {
            $this->_cache = Cache::backend($this->_cache_storage);
        }
        return $this->_cache;
    }

    /**
     * Get the cache method expiration
     */
    public function getCacheExpiration($method, $default = 0)
    {
        return isset($this->_cache_methods[$method]) ? $this->_cache_methods[$method] : $default;
    }

    /**
     * @return Datamap
     */
    public static function instance()
    {
        $name = get_called_class();
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new static;
        }
        return self::$_instances[$name];
    }

    /**
     * Set cache based on get method
     */
    public static function setCache($method, $args = [], $ckey = null)
    {
        if (is_array($method)) {
            foreach ($method as $item_method => $item_args) {
                self::setCache($item_method, $item_args);
            }
            return;
        }
        $ckey || $ckey = self::_genCacheKey($method, $args);
        $data = self::_fetchSource($method, $args);
        if (!empty($data)) {
            self::instance()->cache()->set($ckey, $data, self::instance()->getCacheExpiration($method));
        }
        return $data;
    }

    /**
     * Delete cache based on get method
     */
    public static function delCache($method, $args = [], $ckey = null)
    {
        if (is_array($method)) {
            foreach ($method as $item_method => $item_args) {
                self::delCache($item_method, $item_args);
            }
            return;
        }
        $ckey || $ckey = self::_genCacheKey($method, $args);
        return self::instance()->cache()->delete($ckey);
    }


    public function __call($method, $args)
    {
        // from cache
        if (isset($this->_cache_methods[$method])) {
            $ckey = self::_genCacheKey($method, $args);
            $data = $this->cache()->get($ckey);
            if (null === $data) {
                $data = self::setCache($method, $args, $ckey);
            }
            return $data;
        }

        // from raw
        if (strpos($method, 'get') === 0) {
            return self::_fetchSource($method, $args);
        }

        throw new \Exception('Undefined method '. $method);
    }


    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }


    /**
     * Check and return the fetch method
     */
    protected static function _fetchSource($method, $args)
    {
        $scname = substr($method, 3);
        $scmethod = 'fetch'. $scname;
        if (!method_exists(get_called_class(), $scmethod)) {
            throw new \Exception("Method {$scmethod} not exists");
        }
        return call_user_func_array(array(self::instance(), $scmethod), $args);
    }

    /**
     * Generate the cache key
     */
    protected static function _genCacheKey($method, $args)
    {
        return md5(get_called_class() . $method . json_encode($args));
    }
}