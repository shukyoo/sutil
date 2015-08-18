<?php namespace Sutil\Database\QueryAssemble;

use Sutil\Database\Adapters\AdapterInterface;

class Table
{
    /**
     * @var AdapterInterface
     */
    protected $_adapter;
    protected $_table;

    protected $_selection = '*';

    public function __construct(AdapterInterface $adapter, $table)
    {
        $this->_adapter = $adapter;
        $this->_table = trim($table);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * @param string|array $selection
     */
    public function select($selection)
    {
        if (is_string($selection)) {
            $selection = explode(',', $selection);
        }
        foreach ($selection as $field) {

        }
    }
}