<?php namespace Sutil\DataMap;

abstract class Model
{
    public function __construct(array $data = [])
    {
        foreach ($data as $k=>$v) {
            $this->setProperty($k, $v);
        }
    }

    public function setProperty($key, $value)
    {
        $method = 'set'. $this->_mutateMethod($key);
        if (method_exists($this, $method)) {
            $value = $this->$method($value);
        }
        $this->$key = $value;
    }

    public function getProperty($key)
    {
        $method = 'get'. $this->_mutateMethod($key);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return $this->$key;
    }

    /**
     * Set property magically
     */
    public function __set($key, $value)
    {
        $this->setProperty($key, $value);
    }

    /**
     * Get Attribute magically
     */
    public function __get($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Trans string into camel case
     */
    protected function _mutateMethod($key)
    {
        return str_replace('_', '', ucwords($key, '_'));
    }
}