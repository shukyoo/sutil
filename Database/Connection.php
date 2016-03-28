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
        return $this->getMaster();
    }

    /**
     * @return PDO
     */
    public function getMaster()
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
    public function getSlave()
    {
        if (empty($this->_slave_config) || $this->_transactions > 0) {
            return $this->getMaster();
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
     * @param $sql
     * @param PDO $pdo
     * @return \PDOStatement
     */
    public function prepare($sql, PDO $pdo = null)
    {
        if (null === $pdo) {
            $pdo = $this->getMaster();
        }
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            $errinfo = $pdo->errorInfo();
            throw new \PDOException($errinfo[2], $errinfo[0]);
        }
        return $stmt;
    }


    /**
     * get PDOStatement
     * @param string $sql
     * @param mixed $bind
     * @param int $fetch_mode PDO fetch mode
     * @param mixed $fetch_args
     * @return \PDOStatement
     */
    public function selectPrepare($sql, $bind = null, $fetch_mode = null, $fetch_arg2 = null, $fetch_arg3 = null)
    {
        $stmt = $this->prepare($sql, $this->getSlave());
        $stmt->execute($this->_getBind($bind));
        if (null !== $fetch_mode) {
            $stmt->setFetchMode($fetch_mode, $fetch_arg2, $fetch_arg3);
        }
        return $stmt;
    }


    /**
     * Execute an SQL statement and return the boolean result.
     * @param $sql
     * @param null $bind
     * @param &$stmt
     * @return bool
     */
    public function execute($sql, $bind = null)
    {
        $stmt = $this->prepare($sql, $this->getMaster());
        $res = $stmt->execute($this->_getBind($bind));
        if (!$res) {
            $errinfo = $stmt->errorInfo();
            throw new \PDOException($errinfo[2], $errinfo[0]);
        }
        return $res;
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
     * fetch all with first field as indexed key, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllIndexed($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllGrouped($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
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
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @return array
     */
    public function fetchPairs($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     * @return array
     */
    public function fetchPairsGrouped($sql, $bind = null)
    {
        $data = [];
        foreach ($this->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }

    /**
     * fetch one column value, false returned if nothing or false
     * @return mixed
     */
    public function fetchOne($sql, $bind = null)
    {
        return $this->selectPrepare($sql, $bind)->fetchColumn(0);
    }

    /**
     * Get last insert id
     * @return int|string
     */
    public function getLastInsertId()
    {
        return $this->getMaster()->lastInsertId();
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
            $this->getMaster()->beginTransaction();
        }
    }

    /**
     * Commit the active database transaction.
     * @return void
     */
    public function commit()
    {
        if ($this->_transactions == 1) {
            $this->getMaster()->commit();
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
            $this->getMaster()->rollBack();
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
    protected function _getBind($bind)
    {
        if (null === $bind) {
            return null;
        }
        if (!is_array($bind)) {
            return [$bind];
        }
        return array_values($bind);
    }
}