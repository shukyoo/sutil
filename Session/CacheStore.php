<?php namespace Sutil\Session;

use SessionHandlerInterface;
use Sutil\Cache\Adapter\AdapterInterface;

class CacheStore implements SessionHandlerInterface
{
    protected $_cache = null;
    protected $_lifetime = 1440;

    public function __construct(AdapterInterface $cache, $lifetime)
    {
        $this->_cache = $cache;
        $this->_lifetime = $lifetime;
    }

    public function open($save_path, $session_id)
    {
        return true;
    }

    public function read($session_id)
    {
        return $this->_cache->get($session_id);
    }

    public function write($session_id, $session_data)
    {
        return $this->_cache->set($session_id, $session_data, $this->_lifetime);
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        return $this->_cache->delete($session_id);
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}