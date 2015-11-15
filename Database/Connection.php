<?php namespace Sutil\Database;

class Connection
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
     * Get current database driver name
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
        return new Adapter($config);
    }

    /**
     * Get PDO instance
     * @return \PDO
     */
    public function pdo()
    {
        return $this->master();
    }

    /**
     * Get a master PDO instance
     * @return \PDO
     */
    public function master()
    {
        if (!isset($this->_master_pdos[$this->_master_index])) {
            $this->_master_pdos[$this->_master_index] = $this->_adapter($this->_master_configs[$this->_master_index])->connect();
        }
        return $this->_master_pdos[$this->_master_index];
    }

    /**
     * Get a slave PDO instance
     * @return \PDO
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
        is_array($bind) || $bind = [$bind];
        return $bind;
    }

    /**
     * Run a select statement against the database.
     * @param string $sql
     * @param mixed $bind
     * @param int $fetch_mode PDO fetch mode
     * @return \PDOStatement
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
     * Execute an SQL statement and return the boolean result.
     * @param  string  $sql
     * @param  array   $bind
     * @return bool
     */
    public function execute($sql, $bind = null)
    {
        return $this->master()->prepare($sql)->execute($this->_bind($bind));
    }

    /**
     * Get last insert id
     * @return int|string
     */
    public function lastInsertId()
    {
        return $this->master()->lastInsertId();
    }


    /**
     * Execute a Closure within a transaction.
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
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
     * Start a new database transaction.
     * @return void
     */
    public function beginTransaction()
    {
        ++$this->_transactions;
        if ($this->_transactions == 1) {
            $this->master()->beginTransaction();
        }
    }

    /**
     * Commit the active database transaction.
     * @return void
     */
    public function commit()
    {
        if ($this->_transactions == 1) {
            $this->master()->commit();
        }
        --$this->_transactions;
    }

    /**
     * Rollback the active database transaction.
     * @return void
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
     * Query
     * If thers has space in $base then use it as raw sql, otherwise use as table
     * @param string $base sql|table
     * @param mixed $bind for sql
     * @return Query\Sql|Query\Table
     */
    public function query($base, $bind = null)
    {
        if (strpos($base, ' ')) {
            return $this->sql($base, $bind);
        } else {
            return $this->table($base);
        }
    }

    /**
     * Use raw sql query
     * @param string $sql
     * @param mixed $bind
     * @return Query\Sql
     */
    public function sql($sql, $bind = null)
    {
        return new Query\Sql($this, $sql, $bind);
    }

    /**
     * Use table builder query
     * @param string $table
     * @return Query\Table
     */
    public function table($table)
    {
        return new Query\Table($this, $table);
    }
}
