<?php namespace Sutil\Session;

use Sutil\Cache\Cache;

class Session
{
    // check if started
    protected static $_started = 0;
    protected static $_config = [];


    public static function config(array $config)
    {
        if (empty($config['lifetime'])) {
            $config['lifetime'] = (int)ini_get('session.gc_maxlifetime');
        }
        self::$_config = $config;
    }

    /**
     * Create cache handler
     * @return CacheStore
     */
    protected static function _createCacheHandler()
    {
        $store = empty(self::$_config['storage']) ? null : self::$_config['storage'];
        return new CacheStore(Cache::getAdapter($store), self::$_config['lifetime']);
    }


    /**
     * session start
     */
    public static function start()
    {
        if (self::$_started) {
            return;
        }

        if (!empty(self::$_config['handler'])) {
            $method = '_create'. ucfirst(strtolower(self::$_config['handler'])) .'Handler';
            $handler = self::$method();
            session_set_save_handler(
                array(&$handler, 'open'),
                array(&$handler, 'close'),
                array(&$handler, 'read'),
                array(&$handler, 'write'),
                array(&$handler, 'destroy'),
                array(&$handler, 'gc')
            );
        }

        self::$_started = session_start();
    }


    /**
     * Get session
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Set session
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if has session
     * @param $key
     * @return bool
     */
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Delete session
     * @param $key
     */
    public static function delete($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Get session id
     * @return string
     */
    public static function getId()
    {
        self::start();
        return session_id();
    }

    /**
     * Close session write
     */
    public static function close()
    {
        self::start();
        session_write_close();
    }

    /**
     * Session destroy
     */
    public static function destroy()
    {
        self::start();
        session_unset();
        session_destroy();
    }
}