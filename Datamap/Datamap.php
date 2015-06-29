<?php namespace Sutil\Datamap;

use Sutil\Cache\Cache;

abstract class Datamap
{
    public function __call($method, $args)
    {
        $builder = new Builder($this, Cache::backend());
        return call_user_func_array(array($builder, $method), $args);
    }

    public static function __callStatic($method, $args)
    {
        $class = get_called_class();
        static $instance = array();
        if (!isset($instance[$class])) {
            $instance[$class] = new static;
        }
        return call_user_func_array(array($instance[$class], $method), $args);
    }
}