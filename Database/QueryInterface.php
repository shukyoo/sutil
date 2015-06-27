<?php namespace Sutil\Database;

use Closure;

interface QueryInterface
{

    /**
     * Run a select statement against the database.
     * @param string $sql
     * @param mixed $bind
     * @param int $fetch_mode PDO fetch mode
     * @return array
     */
    public function select($sql, $bind = null, $fetch_mode = null, $fetch_args = null);

    /**
     * Execute an SQL statement and return the boolean result.
     * @param  string  $sql
     * @param  array   $bind
     * @return bool
     */
    public function execute($sql, $bind = null);

    /**
     * Get last insert id
     * @return int|string
     */
    public function lastInsertId();

    /**
     * $sql = 'SELECT * FROM users WHERE gender=?';
     * fetchAll($sql, ['gender' => 'boy']);
     * 
     * @param string $sql
     * @param array $bind
     * @return array with nature index, empty array returned if nothing or false
     */
    public function fetchAll($sql, $bind = null);

    /**
     * The first field will be the indexed key, recommend
     * $sql = 'SELECT id, name, gender FROM users WHERE gender=?';
     * fetchAllIndexed($sql, ['gender' => 'boy'])
     *
     * @param string $sql
     * @param array $bind
     * @return array fetch all with specified index, empty array returned if nothing or false
     */
    public function fetchAllIndexed($sql, $bind = null);

    /**
     * The first key will be the keys of group, recommend
     * $sql = 'SELECT age, id, name FROM users WHERE gender=?';
     * fetchAllGrouped($sql, ['gender' => 'boy'])
     *
     * @param string $sql
     * @param array $bind
     * @return array fetch all grouped with specified field, empty array returned if nothing or false
     */
    public function fetchAllGrouped($sql, $bind = null);
    
    /**
     * $sql = 'SELECT * FROM users WHERE gender=?';
     * fetchAllClass('User', $sql, ['gender' => 'boy'])
     * 
     * @param string|object $class
     * @param string $sql
     * @param array $bind
     * @return array return array of classes, empty array returned if nothing or false
     */
    public function fetchAllClass($class, $sql, $bind = null);
    
    /**
     * $sql = 'SELECT * FROM users WHERE id=?';
     * fetchRow($sql, ['id' => 1])
     * 
     * @param string $sql
     * @param array $bind
     * @return array one row, empty array returned if nothing or false
     */
    public function fetchRow($sql, $bind = null);
    
    /**
     * $sql = 'SELECT * FROM users WHERE id=?';
     * fetchRowClass('User', $sql, ['id' => 1])
     * 
     * @param string|object $class
     * @param string $sql
     * @param array $bind
     * @return object|null return instance of the class, null returned if nothing or false
     */
    public function fetchRowClass($class, $sql, $bind = null);
    
    /**
     * $sql = 'SELECT name FROM users WHERE gender=?';
     * fetchCol($sql, ['gender' => 'boy']);
     * 
     * @param string $sql
     * @param array $bind
     * @return array return first column array, empty array returned if nothing or false
     */
    public function fetchCol($sql, $bind);
    
    /**
     * $sql = 'SELECT id, name FROM users WHERE gender=?';
     * fetchPairs($sql, ['gender' => 'boy'])
     * 
     * @param string $sql
     * @param array $bind
     * @return array return pairs of first column as Key and second column as Value, empty array returned if nothing or false
     */
    public function fetchPairs($sql, $bind = null);

    /**
     * The first field is the keys of group
     * $sql = 'SELECT age, id, name FROM users WHERE gender=?';
     * fetchPairsGrouped($sql, ['gender' => 'boy'])
     *
     * @param string $group_field
     * @param string $sql
     * @param array $bind
     * @return array return grouped pairs of K/V with specified field, empty array returned if nothing of false
     */
    public function fetchPairsGrouped($sql, $bind = null);
    
    /**
     * $sql = 'SELECT name FROM users WHERE id=?';
     * fetchOne($sql, ['id' => 1])
     * 
     * @param string $sql
     * @param array $bind
     * @return mixed return one column value, false returned if nothing or false
     */
    public function fetchOne($sql, $bind = null);

    /**
     * Check if record exists
     */
    public function exists($table, $where = null, $where_bind = null);

    /**
     * Get record count
     */
    public function count($table, $where = null, $where_bind = null);

    /**
     * Insert
     * @param string $table
     * @param array $data
     * @return boolean
     */
    public function insert($table, $data);
    
    /**
     * Update
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @param mixed $where_bind
     * @return boolean
     */
    public function update($table, $data, $where = null, $where_bind = null);

    /**
     * Save
     * Update if exists, or insert
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @param mixed $where_bind
     * @return boolean
     */
    public function save($table, $data, $where = null, $where_bind = null);
    
    /**
     * Delete
     * @param string $table
     * @param mixed $where
     * @param mixed $where_bind
     * @return boolean
     */
    public function delete($table, $where = null, $where_bind = null);
    
    /**
     * Execute a Closure within a transaction.
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(Closure $callback);

    /**
     * Start a new database transaction.
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     * @return void
     */
    public function rollBack();

}