<?php namespace Sutil\Database;

class Connection implements ConnectionInterface
{
    protected $_master_configs = [];
    protected $_master_pdos = [];
    protected $_master_index = 0;

    protected $_slave_configs = [];
    protected $_slave_pdos = [];
    protected $_slave_index = 0;

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


    /**
     * {@inheritDoc}
     */
    public function driver()
    {
        return $this->_driver;
    }


    /**
     * Get adapter
     */
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
     * Parse bind as array
     */
    protected function _bind($bind)
    {
        if ($bind === null) {
            return null;
        }
        is_callable($bind) && $bind = $bind();
        is_array($bind) || $bind = [$bind];
        return $bind;
    }

    /**
     * {@inheritDoc}
     */
    public function select($sql, $bind = null, $fetch_mode = null, $fetch_args = null)
    {
        $stmt = $this->slave()->prepare($sql);
        $stmt->execute($this->_bind($bind));
        if (null !== $fetch_mode) {
            $stmt->setFetchMode($fetch_mode, $fetch_args);
        }
        return $stmt;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($sql, $bind = null)
    {
        return $this->master()->prepare($sql)->execute($this->_bind($bind));
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId()
    {
        return $this->master()->lastInsertId();
    }


    /**
     * {@inheritDoc}
     */
    public function transaction(\Closure $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        ++$this->_transactions;
        if ($this->_transactions == 1) {
            $this->master()->beginTransaction();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        if ($this->_transactions == 1) {
            $this->master()->commit();
        }
        --$this->_transactions;
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        if ($this->_transactions == 1) {
            $this->master()->rollBack();
            $this->_transactions = 0;
        } else {
            --$this->_transactions;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function query($sql = null, $bind = null)
    {
        return new Query($this, $sql, $bind);
    }
}
