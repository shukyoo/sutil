<?php namespace Sutil\Database;

use PDO;

class Connection
{
    protected static $_master_config = [];
    protected static $_slave_config = [];
    protected $_transactions = 0;

    public function __construct(array $config)
    {
        if (!empty($config['slave'])) {
            self::$_slave_config = $config['slave'];
            unset($config['slave']);
        }
        self::$_master_config = $config;
    }

    /**
     * @return PDO
     */
    public function getPDO()
    {
        return $this->master();
    }

    /**
     * @return PDO
     */
    public function master()
    {
        static $pdo = null;
        if (null === $pdo) {
            $pdo = $this->_connect(self::$_master_config);
        }
        return $pdo;
    }

    /**
     * @return PDO
     */
    public function slave()
    {
        if (empty($this->_slave_config) || $this->_transactions > 0) {
            return $this->master();
        }
        static $pdo = null;
        if (null === $pdo) {
            $pdo = $this->_connect(self::$_slave_config);
        }
        return $pdo;
    }

    /**
     * @param array $config
     * @return PDO
     */
    protected static function _connect($config)
    {
        if (empty($config['dsn'])) {
            throw new \InvalidArgumentException('dsn config is required for database connection');
        }
        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
        $options = isset($config['options']) ? $config['options'] : null;
        return new PDO($config['dsn'], $username, $password, $options);
    }


    /**
     * get pdostatment
     * @param string $sql
     * @param mixed $bind
     * @param int $fetch_mode PDO fetch mode
     * @return \PDOStatement
     */
    public function selectPrepare($sql, $bind = null, $fetch_mode = null, $fetch_args = null)
    {
        $stmt = $this->slave()->prepare($sql);
        $stmt->execute($this->_bind($bind));
        if (null !== $fetch_mode) {
            $stmt->setFetchMode($fetch_mode, $fetch_args);
        }
        return $stmt;
    }

    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchAll($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchRow($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @return array
     */
    public function fetchColumn($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     * @param $sql
     * @param null $bind
     * @param &$stmt
     * @param int $row_count
     */
    public function execute($sql, $bind = null, &$stmt = null)
    {
        $stmt = $this->master()->prepare($sql);
        return $stmt->execute($this->_bind($bind));
    }

    /**
     * Get last insert id
     * @return int|string
     */
    public function getLastInsertId()
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
        } catch (\Exception $e) {
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
     * Parse bind as array
     * @param mixed $bind
     * @return null|array
     */
    protected function _bind($bind)
    {
        if ($bind === null) {
            return null;
        }
        if (!is_array($bind)) {
            $bind = [$bind];
        }
        return $bind;
    }
}