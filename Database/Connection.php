<?php namespace Sutil\Database;

class Connection implements ConnectionInterface
{
    public function prepare($sql)
    {

    }

    public function getPDO()
    {

    }

    public function quoteIdentifier($identifier)
    {
        /**
        $segments = explode('.', $value);

        // If the value is not an aliased table expression, we'll just wrap it like
        // normal, so if there is more than one segment, we will wrap the first
        // segments as if it was a table and the rest as just regular values.
        foreach ($segments as $key => $segment)
        {
        if ($key == 0 && count($segments) > 1)
        {
        $wrapped[] = $this->wrapTable($segment);
        }
        else
        {
        $wrapped[] = $this->wrapValue($segment);
        }
        }

        return implode('.', $wrapped);
         */
    }
}