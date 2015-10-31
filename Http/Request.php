<?php namespace Sutil\Http;

class Request
{
    /**
     * Get all input request
     * @return array
     */
    public static function all()
    {
        return array_replace_recursive($_GET, $_POST, $_FILES);
    }

    /**
     * POST or GET
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function request($key, $default = null)
    {
        return filter_has_var(INPUT_POST, $key) ? self::post($key, $default) : self::get($key, $default);
    }

    /**
     * Get a get request data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (isset($_GET[$key]) && is_array($_GET[$key])) {
            return filter_input_array(INPUT_GET, $key, FILTER_SANITIZE_STRING, array(
                'flags' => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => $default]
            ));
        } else {
            return filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING, array(
                'options' => ['default' => $default]
            ));
        }
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     */
    public static function getInt($key, $default = 0)
    {
        return isset($_GET[$key]) ? (int)$_GET[$key] : (int)$default;
    }

    /**
     * Get a post request data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function post($key, $default = null)
    {
        if (isset($_POST[$key]) && is_array($_POST[$key])) {
            return filter_input(INPUT_POST, $key, FILTER_DEFAULT, array(
                'flags' => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => $default]
            ));
        } else {
            return filter_input(INPUT_POST, $key, FILTER_DEFAULT, array(
                'options' => ['default' => $default]
            ));
        }
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     */
    public static function postInt($key, $default = 0)
    {
        return isset($_POST[$key]) ? (int)$_POST[$key] : (int)$default;
    }


    /**
     * Get all files uploaded
     * @return array
     */
    public static function files()
    {
        return $_FILES;
    }

    /**
     * Get a file uploaded
     * @param $key
     * @return array|null
     */
    public static function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
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