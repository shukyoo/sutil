<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;


class Sql implements BuilderInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_sql;

    protected $_bind_prepare = [];

    protected $_bind = [];


    /**
     * @todo in clause  id in?
     * @todo assign where clause  ['id' => 1, 'or tt' => 2]
     */
    public function __construct(ConnectionInterface $connection, $sql, $bind = null)
    {
        $this->_connection = $connection;
        $this->_sql = trim($sql);

        // match bind in order
        if (strpos($sql, '?') || strpos($sql, '{')) {
            preg_match_all('/.*(\?|\{\w+\}).*/iU', $sql, $matches);
            $ph_count = substr_count($sql, '?');
            if (!empty($matches[1])) {
                foreach ($matches[1] as $k=>$item) {
                    if ($item == '?') {
                        $this->_bind[$k] = is_array($bind) ? array_shift($bind) : $bind;
                    } else {
                        $item = trim($item, '{}');
                        $this->_bind_prepare[$item] = $k;
                    }
                }
            }
        }
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Assign where
     */
    public function assign($var, $state)
    {

    }

    /**
     * Assign template
     * @param string $var
     * @param string $state
     * @param null $bind
     */
    public function assignRaw($var, $state, $bind = null)
    {
        $this->_sql = str_replace('{'. $var .'}', ' '. trim($state). ' ', $this->_sql);
        if (null !== $bind && isset($this->_bind_prepare[$var])) {
            $this->_bind[$this->_bind_prepare[$var]] = $bind;
        }
        return $this;
    }


    /**
     * Assign in clause
     */
    public function assignIn()
    {

    }

    /**
     * Assign not in clause
     */
    public function assignNotIn()
    {

    }

    /**
     * Assign limit
     */
    public function assignLimit($var, $number, $page = 1)
    {

    }


    /**
     * {@inheritDoc}
     */
    public function getSql()
    {
        return preg_replace('/\{\w+\}/', '', $this->_sql);
    }

    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        ksort($this->_bind);
        $bind = [];
        array_walk_recursive($this->_bind, function($item) use (&$bind) {
            $bind[] = $item;
        });
        return $bind;
    }

    public function getQuerier()
    {
        return new Querier($this);
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->getQuerier(), $method], $args);
    }
}