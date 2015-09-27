<?php namespace Sutil\Validation;

class Validator
{
    /**
     * single value:
     * check('test', 'required|email|length:10,20')
     * check('test', [
     *     'required' => 'value is required',
     *     'email' => 'value must be a valid email'
     *     'length:10,20' => 'value length must in 10-20'
     * ], $message)
     *
     * multi values:
     * check(['aa' => 'hello', 'bb' => 'world'], 'required | string')
     * check(['aa' => 'hello', 'bb' => 'world'], ['aa' => 'required | string', 'bb' => 'in:1,2,3'])
     *
     * multi values with message:
     * check(['aa' => 'hello', 'bb' => 'world'], [
     *      'aa' => ['required' => 'aa is required', 'string' => 'aa must be a string'],
     *      'bb' => ['in:1,2,3' => 'bb must in 1,2,3']
     * ], $message)
     *
     * @param mixed $value
     * @param mixed $rule
     * @return bool
     */
    public static function check($value, $rule, &$message = '')
    {
        $validator = new Validator();
        return $validator->validate($value, $rule, $message);
    }


    /**
     * Validate
     * @param mixed $value
     * @param mixed $rule
     * @param string $message
     * @return bool
     */
    public function validate($value, $rule, &$message = '')
    {
        if (!is_array($value)) {
            $value = [$value];
            $rule = [$rule];
        } elseif (is_array($value) && !is_array($rule)) {
            $rule_str = $rule;
            $rule = [];
            foreach ($value as $k=>$v) {
                $rule[$k] = $rule_str;
            }
        }
        $methods = get_class_methods($this);
        foreach ($value as $k=>$v) {
            if (empty($rule[$k])) {
                continue;
            }
            if (!is_array($rule[$k])) {
                $rule[$k] = explode('|', $rule[$k]);
                $rule[$k] = array_combine($rule[$k], array_fill(0, count($rule[$k]), ''));
            }
            foreach ($rule[$k] as $method => $msg) {
                if (strpos($method, ':')) {
                    $p = strpos($method, ':');
                    $param = substr($method, $p+1);
                    $method = '_'.trim(substr($method, 0, $p));
                } else {
                    $method = '_'.trim($method);
                    $param = null;
                }
                if (!in_array($method, $methods)) {
                    throw new \Exception('Invalid validation method:'. $method);
                }
                if (!$this->$method($v, $param)) {
                    $message = $msg;
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Not empty
     * @param mixed $value
     * @return bool
     */
    protected function _required($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        return (!empty($value) || $value !== '');
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _date($value)
    {
        if (strtotime($value) === false) {
            return false;
        }
        $date = date_parse($value);
        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _url($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param string $value
     * @param string $param
     * @return bool
     */
    protected function _match($value, $param)
    {
        return (bool)preg_match($param, $value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _string($value)
    {
        return is_string($value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _integer($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _numeric($value)
    {
        return is_numeric($value);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function _array($value)
    {
        return is_array($value);
    }

    /**
     * @param string $value
     * @param int|string $param
     * @return bool
     */
    protected function _length($value, $param)
    {
        $param = explode(',', $param);
        $min = $param[0];
        $max = null;
        if (isset($param[1])) {
            $max = $param[1];
        }
        $len = mb_strlen($value, 'UTF-8');
        return ($len >= $min && (null === $max || $len <= $max));
    }

    /**
     * @param mixed $value
     * @param int|string $param
     * @return bool
     */
    protected function _range($value, $param)
    {
        $param = explode(',', $param);
        $min = $param[0];
        $max = null;
        if (isset($param[1])) {
            $max = $param[1];
        }
        return ($value >= $min && (null === $max || $value <= $max));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _bool($value)
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }

    /**
     * @param mixed $value
     * @param mixed $param
     * @return bool
     */
    protected function _same($value, $param)
    {
        return $value == $param;
    }

    /**
     * If same ignore case sensitive
     * @param mixed $value
     * @param mixed $param
     * @return bool
     */
    protected function _iSame($value, $param)
    {
        return strtolower($value) == strtolower($param);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _ip($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * If is a json
     * @param $value
     * @return bool
     */
    protected function _json($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param mixed $value
     * @param array $param
     * @return bool
     */
    protected function _in($value, $param)
    {
        return in_array($value, $param);
    }

    /**
     * If not in array
     * @param mixed $value
     * @param array $param
     * @return bool
     */
    protected function _notIn($value, $param)
    {
        return !in_array($value, $param);
    }


}