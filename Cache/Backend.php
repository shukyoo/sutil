<?php namespace Sutil\Cache;

use Sutil\Cache\Storage\StorageInterface;

class Backend implements BackendInterface
{
    protected $_driver = '';
    protected $_storage = null;
    protected $_expiration = 0;

    public function __construct($config)
    {
        if (empty($config['driver'])) {
            throw new \Exception('Driver is required for cache setting');
        }
        $this->_driver = strtolower($config['driver']);
        unset($config['driver']);
        if (isset($config['expiration'])) {
            $this->_expiration = (int)$config['expiration'];
        }

        $storage_class = '\\Sutil\\Cache\\Storage\\'. ucfirst($this->_driver);
        $this->_storage = new $storage_class($config);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return $this->_storage->get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $expiration = null)
    {
        if (null === $expiration) {
            $expiration = $this->_expiration;
        }
        return $this->_storage->set($key, $value, $expiration);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->_storage->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        $this->_storage->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $offset = 1)
    {
        return $this->_storage->increment($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $offset = 1)
    {
        return $this->_storage->decrement($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function getData($key, $data_source = null, $expiration = null)
    {
        $key = strtolower($key);
        $data = $this->_storage->get($key);
        if (null === $data) {
            $data = $this->setData($key, $data_source, $expiration);
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function setData($key, $data_source, $expiration = null)
    {
        is_callable($data_source) && $data_source = $data_source();
        if (!empty($data_source)) {
            $key = strtolower($key);
            $this->set($key, $data_source, $expiration);
        }
        return $data_source;
    }

}