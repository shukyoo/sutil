<?php namespace Sutil\Database;
use PDO;
/**
 * Facade for db query
 * config e.g.
 * array(
    'dsn' => '',
    'username' => '',
    'grammar' => 'MyGrammar',
    'slave' => array(
        'dsn' => '',
        'username' => ''
    )
    )
 */
class DB
{
    /**
     * @var Connection
     */
    protected static $_connection;
    protected static $_grammar;

    public static function config(Array $config)
    {
        if (!empty($config['grammar'])) {
            self::$_grammar = $config['grammar'];
            unset($config['grammar']);
        }
        self::$_connection = new Connection($config);
    }


    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchAll($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * fetch all with firest field as indexed key, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllIndexed($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @return array
     */
    public function fetchAllGrouped($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * fetch array of requested class with mapped data, empty array returned if nothing or false
     * @param string|object $class
     * @return array
     */
    public function fetchAllClass($sql, $bind, $class)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @return array
     */
    public function fetchRow($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * get instance of the class with mapped data, false returned if nothing or false
     * @param string|object $class
     * @return object|false
     */
    public function fetchRowClass($sql, $bind, $class)
    {
        return self::$_connection->selectPrepare($sql, $bind, PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)->fetch();
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @return array
     */
    public function fetchColumn($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @return array
     */
    public function fetchPairs($sql, $bind = null)
    {
        return self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     * @return array
     */
    public function fetchPairsGrouped($sql, $bind = null)
    {
        $data = [];
        foreach (self::$_connection->selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_NUM) as $row) {
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
        return self::$_connection->selectPrepare($sql, $bind)->fetchColumn(0);
    }

    /**
     * @param $table
     * @param array $data
     * @return bool
     */
    public function insert($table, array $data)
    {
        return self::query($table)->insert($data);
    }

    /**
     * @param $table
     * @param array $data
     * @param null $where
     * @return bool
     */
    public function update($table, array $data, $where = null)
    {
        return self::query($table)->update($data, $where);
    }

    /**
     * @param $table
     * @param null $where
     * @return bool
     */
    public function delete($table, $where = null)
    {
        return self::query($table)->delete($where);
    }


    /**
     * @param null $table
     * @return Query
     */
    public function query($table = null)
    {
        if (self::$_grammar) {
            $grammar = new self::$_grammar();
        } else {
            $grammar = new Grammar();
        }
        $query = new Query(self::$_connection, $grammar);
        if ($table) {
            $query = $query->from($table);
        }
        return $query;
    }
    

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function __callStatic($method, $args = [])
    {
        return call_user_func_array([self::$_connection, $method], $args);
    }
}
