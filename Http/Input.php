<?php namespace Sutil\Http;

class Input
{
    /**
     * Get all input request
     * @return array
     */
    public static function all()
    {
        return Request::all();
    }

    /**
     * Get a get request data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Request::get($key, $default);
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     */
    public static function getInt($key, $default = 0)
    {
        return Request::getInt($key, $default);
    }

    /**
     * Get a post request data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function post($key, $default = null)
    {
        return Request::post($key, $default);
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     */
    public static function postInt($key, $default = 0)
    {
        return Request::postInt($key, $default);
    }


    /**
     * Get all files uploaded
     * @return array
     */
    public static function files()
    {
        return Request::files();
    }

    /**
     * Get a file uploaded
     * @param $key
     * @return array|null
     */
    public static function file($key)
    {
        return Request::file($key);
    }
}