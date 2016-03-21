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
    public static function connect()
    {
        static $connection = null;
        if (null === $connection) {
            $connection = new Connection(self::$_config);
        }
        return $connection;
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
        $query = new Query(self::connect(), $grammar);
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
        return call_user_func_array([self::connect(), $method], $args);
    }
}
