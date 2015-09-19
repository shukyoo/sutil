<?php namespace Sutil\Database\QueryBuilder;

class MysqlBuilder extends Builder
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