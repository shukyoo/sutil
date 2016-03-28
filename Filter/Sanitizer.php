<?php namespace Sutil\Filter;

class Sanitizer
{
    /**
     * single value:
     * filter('test', 'toUpper|trim:/')
     *
     * multi values:
     * check(['aa' => 'Hello', 'bb' => 'World'], 'toLower | stripChars:!@#')
     * check(['aa' => 22.12, 'bb' => 34.1789], ['aa' => 'toInt', 'bb' => 'toFloat:3'])
     *
     * @param mixed $value
     * @param mixed $rule
     * @return bool
     */
    public static function filter($value, $rule)
    {
        return self::getInstance()->sanitize($value, $rule);
    }

    /**
     * @return Sanitizer
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * @param mixed $value
     * @param mixed $rule
     * @return mixed
     */
    public function sanitize($value, $rule)
    {
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                if (is_array($rule)) {
                    $r = empty($rule[$k]) ? '' : $rule[$k];
                } else {
                    $r = $rule;
                }
                if ($r) {
                    $value[$k] = $this->sanitize($v, $r);
                }
            }
        } else {
            $rule = explode('|', $rule);
            $methods = get_class_methods($this);
            foreach ($rule as $method) {
                if (strpos($method, ':')) {
                    $p = strpos($method, ':');
                    $param = substr($method, $p + 1);
                    $method = trim(substr($method, 0, $p));
                } else {
                    $method = trim($method);
                    $param = null;
                }
                if (!in_array($method, $methods)) {
                    throw new \RuntimeException('Invalid validation method:'. $method);
                }
                $value = $this->$method($value, $param);
            }
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return int|array
     */
    public function toInt($value)
    {
        return intval($value);
    }

    /**
     * @param mixed $value
     * @param int $decimals
     * @return float|array
     */
    public function toFloat($value, $decimals = 2)
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * @param string $value
     * @return string
     */
    public function toLower($value)
    {
        return strtolower($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function toUpper($value)
    {
        return strtoupper($value);
    }

    /**
     * @param string $value
     * @param string $char
     * @return string
     */
    public function trim($value, $char = ' ')
    {
        return trim($value, $char);
    }

    /**
     * @param string $value
     * @param string $char
     * @return string
     */
    public function trimLeft($value, $char = ' ')
    {
        return ltrim($value, $char);
    }

    /**
     * @param string $value
     * @param string $char
     * @return string
     */
    public function trimRight($value, $char = ' ')
    {
        return rtrim($value, $char);
    }

    /**
     * @param string $value
     * @param int $flags
     * @return string
     */
    public function encodeHtmlChars($value, $flags = null)
    {
        return htmlspecialchars($value, $flags);
    }

    /**
     * @param string $value
     * @param string $allowable_tags
     * @return string
     */
    public function stripTags($value, $allowable_tags = '')
    {
        return strip_tags($value, $allowable_tags);
    }

    /**
     * @param string $value
     * @param string|array $chars
     * @return string
     */
    public function stripChars($value, $chars)
    {
        if (!is_array($chars)) {
            $chars = str_split($chars);
        }
        return str_replace($chars, '', $value);
    }

    /**
     * strip ASCII value less than 32
     * @param string $value
     * @return string
     */
    public function stripLow($value)
    {
        return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
    }

    /**
     * @param string $value
     * @return string
     */
    public function addSlashes($value)
    {
        return addslashes($value);
    }
}