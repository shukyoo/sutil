<?php namespace Sutil\DataMap;

use Sutil\Database\DB;

abstract class Mapper
{
    /**
     * Specify the connection of the database
     */
    protected $_connection = null;

    /**
     * @var @var \Sutil\Database\Query
     */
    protected $_query = null;

    protected $_table;

    protected $_model;


    public function __construct()
    {
        if (!$this->_table) {
            throw new \Exception('Please set the table name for your model');
        }
        $this->_query = DB::connection($this->_connection);

        $this->_connection = DB::connection($this->_connection_name);

        $this->_query = DB::query($this->_table);
        if (!$this->_model) {
            $this->_model = str_replace('_', '', ucwords($this->_table, '_'));
        }
        if ($clause) {
            foreach ($clause as $k=>$v) {
                $method = 'set' . (str_replace('_', '', ucwords($k, '_')));
                if (method_exists($this, $method)) {
                    $this->$method($v);
                } else {
                    $this->_query->where($k, $v);
                }
            }
        }
    }

    /**
     * @return Datamap
     */
    public static function instance()
    {
    }

    /**
     * Call statically
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::instance(), $method], $args);
    }
}

