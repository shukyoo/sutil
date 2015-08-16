<?php namespace Sutil\DataMap;

use Sutil\Database\DB;

abstract class Mapper
{
    /**
     * Specify the connection of the database
     */
    protected static $_connection = null;

    /**
     * @param string $sql
     * @return Query
     */
    public static function query($sql, $where = null)
    {
        return new Query(DB::connection(self::$_connection), $sql, $where);
    }

}

