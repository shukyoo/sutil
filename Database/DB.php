<?php namespace Sutil\Database;
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
    protected static $_config;
    protected static $_grammar;

    public static function config(array $config)
    {
        if (!empty($config['grammar'])) {
            self::$_grammar = $config['grammar'];
            unset($config['grammar']);
        }
        self::$_config = $config;
    }

    /**
     * @return Connection
     */
    public static function getConnection()
    {
        static $connection = null;
        if (null === $connection) {
            $connection = new Connection(self::$_config);
        }
        return $connection;
    }

    /**
     * @param $table
     * @param array $data
     * @return bool
     */
    public static function insert($table, array $data)
    {
        return self::query($table)->insert($data);
    }

    /**
     * @param $table
     * @param array $data
     * @param null $where
     * @return bool
     */
    public static function update($table, array $data, $where = null)
    {
        return self::query($table)->update($data, $where);
    }

    /**
     * @param $table
     * @param null $where
     * @return bool
     */
    public static function delete($table, $where = null)
    {
        return self::query($table)->delete($where);
    }


    /**
     * @param null $table
     * @return Query
     */
    public static function query($table = null)
    {
        if (self::$_grammar) {
            $grammar = new self::$_grammar();
        } else {
            $grammar = new Grammar();
        }
        $query = new Query(self::getConnection(), $grammar);
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
    public static function __callStatic($method, $args = [])
    {
        return call_user_func_array([self::getConnection(), $method], $args);
    }
}
