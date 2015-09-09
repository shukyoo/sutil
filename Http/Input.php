<?php namespace Sutil\Http;

class Input
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
     * Get a get request data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (isset($_GET[$key]) && is_array($_GET[$key])) {
            return filter_input(INPUT_GET, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        } else {
            return filter_input(INPUT_GET, $key);
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
            return filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        } else {
            return filter_input(INPUT_POST, $key);
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
}