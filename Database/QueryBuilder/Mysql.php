<?php namespace Sutil\Database\QueryBuilder;

class Mysql extends BuilderAbstract
{
    /**
     * @param $identifier
     * @return string
     */
    protected function _quoteIdentifier($identifier)
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}