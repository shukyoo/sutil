<?php namespace Sutil\Database\Query\Grammars;

class Mysql extends GrammarAbstract
{
    /**
     * {@inheritDoc}
     */
    protected function _wrap($field)
    {
        return '`'.str_replace('`', '``', $field).'`';
    }
}