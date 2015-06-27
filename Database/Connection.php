<?php namespace Sutil\Database;

use PDO;

class Connection implements ConnectionInterface
{
    protected $_master_configs = [];
    protected $_master_pdos = [];
    protected $_master_index = 0;

    protected $_slave_configs = [];
    protected $_slave_pdos = [];
    protected $_slave_index = 0;

    protected $_driver = '';


    public function __construct(array $config)
    {
        if (empty($config['driver'])) {
            throw new \Exception('Driver is required in the database config');
        }
        $this->_driver = strtolower($config['driver']);
        unset($config['driver']);

        if (!empty($config['slaves'])) {
            $this->_slave_configs = $config['slaves'];
            unset($config['slaves']);
        } elseif (!empty($config['slave'])) {
            $this->_slave_configs[] = $config['slave'];
            unset($config['slave']);
        }
        if (!empty($config['masters'])) {
            $this->_master_configs = $config['masters'];
        } else {
            $this->_master_configs[] = $config;
        }
        if (empty($this->_master_configs)) {
            throw new \Exception('Master database config is empty');
        }

        // random of the index for the pdo instance
        $master_count = count($this->_master_configs);
        if ($master_count > 1) {
            $this->_master_index = mt_rand(0, $master_count - 1);
        }

        $slave_count = count($this->_slave_configs);
        if ($slave_count > 1) {
            $this->_slave_index = mt_rand(0, $slave_count - 1);
        }
    }


    protected function _adapter($config)
    {
        $driver = '\\Sutil\\Database\\Adapters\\'. ucfirst($this->_driver);
        if (!class_exists($driver)) {
            throw new \Exception("The driver {$driver} has not been implemented");
        }
        return new $driver($config);
    }

    /**
     * {@inheritDoc}
     */
    public function master()
    {
        if (!isset($this->_master_pdos[$this->_master_index])) {
            $this->_master_pdos[$this->_master_index] = $this->_adapter($this->_master_configs[$this->_master_index])->connect();
        }
        return $this->_master_pdos[$this->_master_index];
    }

    /**
     * {@inheritDoc}
     */
    public function slave()
    {
        if (empty($this->_slave_configs)) {
            return $this->master();
        }
        if (!isset($this->_slave_pdos[$this->_slave_index])) {
            $this->_slave_pdos[$this->_slave_index] = $this->_adapter($this->_slave_configs[$this->_slave_index])->connect();
        }
        return $this->_slave_pdos[$this->_slave_index];
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifier($identifier)
    {
        $adapter = '\\Sutil\\Database\\Adapters\\'. ucfirst($this->_driver);
        $segments = explode('.', $identifier);
        $quoted = [];
        foreach ($segments as $k => $seg) {
            $quoted[] = $adapter::quoteIdentifier($seg);
        }
        return implode('.', $quoted);
    }
}