<?php namespace Sutil\Cache\Storage;

class Memcached implements StorageInterface
{
    const DEFAULT_PORT = 11211;
    const DEFAULT_WEIGHT = 1;

    protected $_mc = null;

    /**
     * Create a instance based on the given configuration
     * Based on php memcache extension
     * Config example:
     * array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 1)
     * array(
     *     array('host' => '127.0.0.1')
     *     array('host' => '127.0.0.1', 'port' => 11211),
     *     array('host' => '127.0.0.1', 'weight' => 1)
     * )
     *
     * @param array $config the configuration
     */
    public function __construct(array $config = array())
    {
        if (!extension_loaded('memcached')) {
            throw new \Exception('Memcached extension not loaded');
        }
        $this->_mc = new \Memcached;
        if (!empty($config['host'])) {
            $config = [$config];
        }
        foreach ($config as $server) {
            $host = $server['host'];
            $port = empty($server['port']) ? self::DEFAULT_PORT : $server['port'];
            $weight = isset($server['weight']) ? (int)$server['weight'] : self::DEFAULT_WEIGHT;
            $this->_mc->addServer($host, $port, $weight);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $value = $this->_mc->get($key);
        return ($value === false) ? $default : $value;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $expiration = 0)
    {
        return $this->_mc->set($key, $value, 0, $expiration);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->_mc->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        $this->_mc->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $offset = 1)
    {
        return $this->_mc->increment($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $offset = 1)
    {
        return $this->_mc->decrement($key, $offset);
    }
}