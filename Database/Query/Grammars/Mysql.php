<?php namespace Sutil\Database\Query\Grammars;

class Mysql extends GrammarBase
{
    /**
     * {@inheritDoc}
     */
    public function wrap($field)
    {
        return '`'.str_replace('`', '``', $field).'`';
    }
}