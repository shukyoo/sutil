<?php namespace Sutil\Database;

interface ConnectionInterface
{
    /**
     * Get the driver
     */
    public function driver();

    /**
     * Get a master PDO instance
     * @return \PDO
     */
    public function master();

    /**
     * Get a slave PDO instance
     * @return \PDO
     */
    public function slave();

    /**
     * Run a select statement against the database.
     * @param string $sql
     * @param mixed $bind
     * @param int $fetch_mode PDO fetch mode
     * @return \PDOStatement
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
     * Execute a Closure within a transaction.
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(\Closure $callback);

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

    /**
     * Query
     * If thers has space in $base then use it as raw sql, otherwise use as table
     * @param string $base sql|table
     * @param mixed $bind for sql
     * @return Query\Sql|Query\Table
     */
    public function query($base, $bind = null);


    /**
     * Use raw sql query
     * @param string $sql
     * @param mixed $bind
     * @return Query\Sql
     */
    public function sql($sql, $bind = null);

    /**
     * Use table builder query
     * @param string $table
     * @return Query\Table
     */
    public function table($table);

}