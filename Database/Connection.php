<?php namespace Sutil\Database;

use PDO;

class Connection implements ConnectionInterface
{
    protected $_master_configs = [];
    protected $_master_adapters = [];
    protected $_master_pdos = [];

    protected $_slave_configs = [];
    protected $_slave_adapters = [];
    protected $_slave_pdos = [];

    public function __construct(array $config)
    {
        if (!empty($config['slaves'])) {
            $this->_slave_configs = $config['slaves'];
            unset($config['slaves']);
        } elseif (!empty($config['slave'])) {
            $this->_slave_configs[] = $config['slave'];
            unset($config['slave']);
        }
        if (!empty($config['driver'])) {
            unset($config['masters']);
            $this->_master_configs[] = $config;
        } elseif (!empty($config['masters'])) {
            $this->_master_configs = $config['masters'];
        }
        if (empty($this->_master_configs)) {
            throw new \Exception('Master database config is empty');
        }
    }

    /**
     * @return \Sutil\Database\Adapters\AdapterInterface
     */
    protected function _masterAdapter($index = 0)
    {
        if (!isset($this->_master_adapters[$index])) {
            $driver = $this->_master_configs[$index]['driver'];
            $driver = '\\Sutil\\Database\\Adapters\\'.ucfirst(strtolower($driver));
            $this->_master_adapters[$index] = new $driver($this->_master_configs[$index]);
        }
        return $this->_master_adapters[$index];
    }

    /**
     * @return \Sutil\Database\Adapters\AdapterInterface
     */
    protected function _slaveAdapter($index = 0)
    {
        if (!isset($this->_slave_adapters[$index])) {
            $driver = $this->_slave_configs[$index]['driver'];
            $driver = '\\Sutil\\Database\\Adapters\\'. ucfirst(strtolower($driver));
            $this->_slave_adapters[$index] = new $driver($this->_slave_configs[$index]);
        }
        return $this->_slave_adapters[$index];
    }

    /**
     * @return PDO
     */
    protected function _master()
    {
        $index = $this->_index(count($this->_master_configs));
        if (!isset($this->_master_pdos[$index])) {
            $this->_master_pdos[$index] = $this->_masterAdapter($index)->connect();
        }
        return $this->_master_pdos[$index];
    }

    /**
     * @return PDO
     */
    protected function _slave()
    {
        if (empty($this->_slave_configs)) {
            return $this->_master();
        }
        $index = $this->_index(count($this->_slave_configs));
        if (!isset($this->_slave_pdos[$index])) {
            $this->_slave_pdos[$index] = $this->_slaveAdapter($index)->connect();
        }
        return $this->_slave_pdos[$index];
    }

    protected function _index($count)
    {
        return ($count > 1) ? mt_rand(0, $count - 1) : 0;
    }

    public function prepare($sql)
    {
        $sql = trim($sql);
        if (stripos($sql, 'SELECT') === 0) {
            return $this->_slave()->prepare($sql);
        } else {
            return $this->_master()->prepare($sql);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPDO()
    {
        return $this->_master();
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifier($identifier)
    {
        $segments = explode('.', $identifier);
        $quoted = [];
        foreach ($segments as $k => $seg) {
            $quoted[] = $this->_masterAdapter()->quoteIdentifier($seg);
        }
        return implode('.', $quoted);
    }
}