<?php namespace Sutil\Http;

defined('ST_DEFAULT') || define('ST_DEFAULT', 10);
defined('ST_RAW') || define('ST_RAW', 11);
defined('ST_INT') || define('ST_INT', 12);

class Request
{
    /**
     * Get query params, special charecters will be stripped by default
     * Specify ST_RAW for return raw data
     * Specify ST_INT for return int
     *
     * Example:
     * Request::get('id', ST_INT);
     * Request::get('test');
     *
     * @param string $key
     * @param int $type ST_RAW | ST_INT
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $type = ST_DEFAULT, $default = null)
    {
        if (isset($_GET[$key]) && is_array($_GET[$key])) {
            $var = filter_input(INPUT_GET, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            foreach ($var as $k=>$v) {
                $v = str_replace(['<', '>', '\'', '"', '%', '`'], '', $v);
                $var[$k] = self::_sanitize($v, $type);
            }
        } else {
            $var = filter_input(INPUT_GET, $key);
            $var = str_replace(['<', '>', '\'', '"', '%', '`'], '', $var);
            $var = self::_sanitize($var, $type, $default);
        }

        return $var;
    }


    /**
     * Get post params,special charecters will be stripped by rule of FILTER_SANITIZE_SPECIAL_CHARS
     * Specify ST_RAW for return raw data
     * Specify ST_INT for return int
     *
     * @param string $key
     * @param int $type
     * @param mixed $default
     * @return mixed
     */
    public static function post($key, $type = ST_DEFAULT, $default = null)
    {
        if (isset($_POST[$key]) && is_array($_POST[$key])) {
            $var = filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            foreach ($var as $k=>$v) {
                $var[$k] = self::_sanitize($v, $type);
            }
        } else {
            $var = filter_input(INPUT_POST, $key);
            $var = self::_sanitize($var, $type, $default);
        }
        return $var;
    }

    /**
     * Request params
     */
    public static function input($key, $type = ST_DEFAULT, $default = null)
    {
        return isset($_POST[$key]) ? self::post($key, $type, $default) : self::get($key, $type, $default);
    }


    /**
     * Sanitize
     */
    protected static function _sanitize($var, $type = ST_DEFAULT, $default = null)
    {
        if (null !== $default && (is_null($var) || $var === '')) {
            $var = $default;
        }
        if ($type == ST_INT) {
            return (int)$var;
        } elseif ($type == ST_RAW) {
            return $var;
        } else {
            return filter_var(trim($var), FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }


    /**
     * Get ip address safely
     * @return string
     */
    public static function ip()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key){
            if (array_key_exists($key, $_SERVER)){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Get host
     */
    public static function host()
    {
        return filter_input(INPUT_SERVER, 'HTTP_HOST');
    }

}