<?php namespace Sutil\DataService;

use Sutil\Database\DB;
use Sutil\Database\ConnectionInterface;

abstract class Service
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->_connection = $connection;
    }


    /**
     * Rebuild input data
     * @param $data
     */
    protected function _put(&$data, $addon = null)
    {
        foreach ($data as $key => $value) {
            $method = 'put'. $this->_mutateMethod($key);
            if (method_exists($this, $method)) {
                $data[$key] = $this->$method($value);
            }
        }
        if (!empty($addon)) {
            foreach ($addon as $key => $value) {
                $data[$key] = $value;
            }
        }
        return $data;
    }


    /**
     * Trans string into camel case
     */
    protected function _mutateMethod($key)
    {
        return str_replace('_', '', ucwords($key, '_'));
    }
}