<?php namespace Sutil\ClassLoader;

class ClassLoader
{
    protected static $_directories = array();
    protected static $_registered = false;

    /**
     * Load the given class file.
     *
     * @param  string  $class
     * @return bool
     */
    public static function load($class)
    {
        $class = ltrim($class, '\\');
        $class = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';

        foreach (static::$_directories as $directory) {
            $directory = rtrim($directory, '/');
            if (file_exists($path = $directory . DIRECTORY_SEPARATOR . $class)) {
                require_once $path;
            }
        }
    }

    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public static function register()
    {
        if (!static::$_registered) {
            static::$_registered = spl_autoload_register(array('self', 'load'));
        }
    }

    /**
     * Add directories to the class loader.
     *
     * @param  string|array  $_directories
     * @return void
     */
    public static function addDirectories($_directories)
    {
        static::$_directories = array_merge(static::$_directories, (array)$_directories);
        static::$_directories = array_unique(static::$_directories);
    }
    
    /**
     * Remove directories from the class loader.
     *
     * @param  string|array  $_directories
     * @return void
     */
    public static function removeDirectories($_directories = null)
    {
        if (is_null($_directories)) {
            static::$_directories = array();
        } else {
            $_directories = (array)$_directories;
            static::$_directories = array_filter(static::$_directories, function($directory) use ($_directories) {
                return (!in_array($directory, $_directories));
            });
        }
    }

    /**
     * Gets all the directories registered with the loader.
     *
     * @return array
     */
    public static function getDirectories()
    {
        return static::$_directories;
    }

}

ClassLoader::register();