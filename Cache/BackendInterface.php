<?php namespace Sutil\Cache;

interface BackendInterface
{
    /**
     * Get cache data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Set cache data
     * @param string $key
     * @param mixed $value
     * @param int $expiration second,0 for forever
     */
    public function set($key, $value, $expiration = 0);

    /**
     * Delete cache data
     * @param string $key
     */
    public function delete($key);

    /**
     * Remove all items from the cache.
     * @return void
     */
    public function flush();

    /**
     * Increment the value of an item in the cache.
     * @param  string  $key
     * @param  int   $offset
     * @return int|false return the new value or false
     */
    public function increment($key, $offset = 1);

    /**
     * Decrement the value of an item in the cache.
     * @param  string  $key
     * @param  int   $offset
     * @return int|false return the new value or false
     */
    public function decrement($key, $offset = 1);

    /**
     * Get the data
     * If date not exists, the data from the callback will be set to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @return mixed
     */
    public function getData($key, $data_source = null, $expiration = null);

    /**
     * Set the data to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @return mixed
     */
    public function setData($key, $data_source, $expiration = null);
}