<?php namespace Sutil\Database;
/**
 * Facade for db query
 * config e.g.
 * array(
    'dsn' => '',
    'username' => '',
    'grammar' => 'MyGrammar',
    'connection' => 'MyConnection',
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
     * @param null|string $table
     * @return Query
     */
    public static function query($table = null)
    {
        $query = new Query(self::connect(), self::_getGrammar());
        if ($table) {
            $query = $query->from($table);
        }
        return $query;
    }

    /**
     * @param $table
     * @param $data
     * @return bool
     */
    public static function insert($table, $data)
    {
        return self::query($table)->insert($data);
    }

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return bool
     */
    public static function update($table, $data, $where)
    {
        return self::query($table)->where($where)->update($data);
    }

    /**
     * @param $table
     * @param $where
     * @return bool
     */
    public static function delete($table, $where)
    {
        return self::query($table)->where($where)->delete();
    }

    /**
     * @return Grammar
     */
    protected static function _getGrammar()
    {
        static $grammar = null;
        if (null === $grammar) {
            if (self::$_grammar && self::$_grammar instanceof Grammar) {
                $grammar = (self::$_grammar instanceof Grammar) ? self::$_grammar : (new self::$_grammar());
            } else {
                $grammar = new Grammar();
            }
        }
        return $grammar;
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
