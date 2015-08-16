<?php namespace Sutil\DataMap;

abstract class Model
{
    protected $_attributes = [];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $k=>$v) {
            $this->setAttribute($k, $v);
        }
    }

    public function setAttribute($key, $value)
    {
        $method = 'set'. $this->_mutateMethod($key);
        if (method_exists($this, $method)) {
            $value = $this->$method($value);
        }
        $this->_attributes[$key] = $value;
    }

    public function getAttribute($key)
    {
        $method = 'get'. $this->_mutateMethod($key);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return isset($this->_attributes[$key]) ? $this->_attributes[$key] : null;
    }


    /**
     * Set Attribute magically
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Get Attribute magically
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Trans string into camel case
     */
    protected function _mutateMethod($key)
    {
        return str_replace('_', '', ucwords($key, '_'));
    }
}