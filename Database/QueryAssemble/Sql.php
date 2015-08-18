<?php namespace Sutil\Database\QueryAssemble;


class Sql
{
    protected $_sql;

    protected $_bind_prepare = [];

    protected $_bind = [];


    public function __construct($sql, $bind = null)
    {
        $this->_sql = trim($sql);

        // match bind in order
        if (strpos($sql, '?') || strpos($sql, '{')) {
            preg_match_all('/.*(\?|\{\w+\}).*/iU', $sql, $matches);
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

    /**
     * Assign template
     * @param string $var
     * @param string $state
     * @param null $bind
     */
    public function assign($var, $state, $bind = null)
    {
        $this->_sql = str_replace('{'. $var .'}', ' '. trim($state). ' ', $this->_sql);
        if (null !== $bind && isset($this->_bind_prepare[$var])) {
            $this->_bind[$this->_bind_prepare[$var]] = $bind;
        }
        return $this;
    }

    /**
     * Get the final sql
     * @return string
     */
    public function getSql()
    {
        return preg_replace('/\{\w+\}/', '', $this->_sql);
    }

    /**
     * Get the final bind
     * @return array
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
}