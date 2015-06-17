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

    protected $_driver = '';
    protected $_transactions = 0;

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
    }

    /**
     * @return \Sutil\Database\Adapters\AdapterInterface
     */
    protected function _masterAdapter($index = 0)
    {
        if (!isset($this->_master_adapters[$index])) {
            $this->_master_adapters[$index] = $this->_adapter($this->_master_configs[$index]);
        }
        return $this->_master_adapters[$index];
    }

    /**
     * @return \Sutil\Database\Adapters\AdapterInterface
     */
    protected function _slaveAdapter($index = 0)
    {
        if (!isset($this->_slave_adapters[$index])) {
            $this->_slave_adapters[$index] = $this->_adapter($this->_slave_configs[$index]);
        }
        return $this->_slave_adapters[$index];
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
     * @return PDO
     */
    protected function _master($index = null)
    {
        null === $index && $index = $this->_index(count($this->_master_configs));
        if (!isset($this->_master_pdos[$index])) {
            $this->_master_pdos[$index] = $this->_masterAdapter($index)->connect();
        }
        return $this->_master_pdos[$index];
    }

    /**
     * @return PDO
     */
    protected function _slave($index = null)
    {
        if (empty($this->_slave_configs)) {
            return $this->_master($index);
        }
        null === $index && $index = $this->_index(count($this->_slave_configs));
        if (!isset($this->_slave_pdos[$index])) {
            $this->_slave_pdos[$index] = $this->_slaveAdapter($index)->connect();
        }
        return $this->_slave_pdos[$index];
    }

    /**
     * Get random index,for multi slaves/masters random selection
     * @param int $count
     * @return int
     */
    protected function _index($count)
    {
        return ($count > 1) ? mt_rand(0, $count - 1) : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($sql)
    {
        $sql = trim($sql);
        if (stripos($sql, 'SELECT') === 0) {
            return $this->_slave()->prepare($sql);
        } else {
            // This index for separated transactions, ensure it's in same pdo instance
            $index = $this->_transactions > 0 ? 0 : null;
            return $this->_master($index)->prepare($sql);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        ++$this->_transactions;
        if ($this->_transactions == 1) {
            $this->_master(0)->beginTransaction();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        if ($this->_transactions == 1) {
            $this->_master(0)->commit();
        }
        --$this->_transactions;
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        if ($this->_transactions == 1) {
            $this->_transactions = 0;
            $this->_master(0)->rollBack();
        } else {
            --$this->_transactions;
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