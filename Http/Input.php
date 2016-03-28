<?php namespace Sutil\Http;

use Sutil\Filter\Validator;
use Sutil\Filter\Sanitizer;
use Sutil\Session\Session;

abstract class Input
{
    protected $_input;
    protected $_data = [];
    protected $_is_valid = true;
    protected $_message = '';

    public function __construct($input = null, $options = ['with_message' => false, 'with_input' => false, 'invalid_call' => null])
    {
        $this->_input = (null === $input) ? Request::all() : $input;

        foreach ($this->_rules() as $k=>$item) {
            $this->_data[$k] = isset($this->_input[$k]) ? $this->_input[$k] : '';
            if (!empty($item['set'])) {
                $this->_data[$k] = $item['set']($this->_input);
            }
            if (!empty($item['sanitize'])) {
                $this->_data[$k] = Sanitizer::filter($this->_data[$k], $item['sanitize']);
            }
            if (!empty($item['validate']) && !Validator::check($this->_data[$k], $item['validate'], $this->_message)) {
                $this->_is_valid = false;
            }
        }

        if (!empty($options['with_message'])) {
            Session::set('_input_msg', $this->getMessage());
        }
        if (!empty($options['with_input'])) {
            Session::set('_input_data', $this->getInput());
        }
        if (!$this->isValid() && !empty($options['invalid_call']) && is_callable($options['invalid_call'])) {
            return $options['invalid_call']($this);
        }
    }

    /**
     * Implement and set rules
     */
    abstract protected function _rules();

    /**
     * If valid
     * @return boolean
     */
    public function isValid()
    {
        return $this->_is_valid;
    }

    /**
     * Get error message
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Get sanitized data
     * @return array
     */
    public function getData($key = null, $default = null)
    {
        if (null !== $key) {
            return isset($this->_data[$key]) ? $this->_data[$key] : $default;
        } else {
            return $this->_data;
        }
    }

    /**
     * Get input data
     * @return array|null
     */
    public function getInput($key = null, $default = null)
    {
        if (null !== $key) {
            return isset($this->_input[$key]) ? $this->_input[$key] : $default;
        } else {
            return $this->_input;
        }
    }

    /**
     * Get value
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }
}