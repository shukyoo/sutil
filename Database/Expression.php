<?php namespace Sutil\Database;

class Expression
{

    /**
     * The value of the expression.
     * @var mixed
     */
    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

}
