<?php namespace Sutil\Cache\Storage;

class Redis implements StorageInterface
{
    const DEFAULT_PORT = 6379;

    /**
     * @var |Redis
     */
    protected $_redis = null;

    /**
     * Create a new redis instance based on the given configuration
     * Based on the php redis extension
     * Config example:
     * array('host' => '127.0.0.1', 'port' => '6379', 'auth' => 'xxx')
     * array(
     *     'cluster' => 'mycluster'
     * )
     * array(
     *     'cluster' => array(
     *         'localhost:7000', 'localhost2:7001', 'localhost:7002'
     *     )
     * )
     *
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('Redis extension not loaded.');
        }
        if (!empty($config['host'])) {
            $this->_redis = new \Redis;
            $host = $config['host'];
            $port = empty($config['port']) ? self::DEFAULT_PORT : $config['port'];
            $this->_redis->connect($host, $port);
            if (!empty($config['auth'])) {
                $this->_redis->auth($config['auth']);
            }
        } elseif (!empty($config['cluster'])) {
            if (is_array($config['cluster'])) {
                $this->_redis = new RedisCluster(NULL, $config['cluster']);
            } else {
                $this->_redis = new RedisCluster($config['cluster']);
            }
        } else {
            throw new \Exception('Invalid configuration');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $value = $this->_redis->get($key);
        if ($value === false) {
            return $default;
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return unserialize($value);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $expiration = 0)
    {
        $value = is_numeric($value) ? $value : serialize($value);
        if ($expiration) {
            return $this->_redis->setex($key, $expiration, $value);
        } else {
            return $this->_redis->set($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->_redis->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        $this->_redis->flushDB();
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $offset = 1)
    {
        return $this->_redis->incrBy($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $offset = 1)
    {
        return $this->_redis->decrBy($key, $offset);
    }
}