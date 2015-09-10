<?php namespace Sutil\Database\QueryBuilders;

class Sql
{
    protected $_sql;

    protected $_assign_prepare = [];

    protected $_sql_prepare = [];

    protected $_bind_prepare = [];


    public function __construct($sql, $bind = null)
    {
        $this->_sql = trim($sql);

        // parse sql
        $this->_parse($sql, $bind);
    }

    /**
     * Assign template
     * @param string $var
     * @param string $state
     * @param null $bind
     * @return Sql
     */
    public function assign($var, $state, $bind = null)
    {
        if (isset($this->_assign_prepare[$var])) {
            $this->_parse($state, $bind, $this->_assign_prepare[$var]);
        }
        return $this;
    }


    /**
     * Parse SQL template
     * @param $str
     * @param null $bind
     * @param array $pre_key
     */
    protected function _parse($str, $bind = null, $pre_key = [])
    {
        $parts = preg_split('/([\?\#]|\{\w+\})/', $str, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $k=>$item) {

            // variable
            if (strpos($item, '{') === 0) {
                $this->_assign_prepare[trim($item, '{}')] = array_merge($pre_key, [$k]);
                continue;
            }

            // Prepare for nested array value set
            $bind_prepare = &$this->_bind_prepare;
            $sql_prepare = &$this->_sql_prepare;
            foreach ($pre_key as $i) {
                $bind_prepare = &$bind_prepare[$i];
                $sql_prepare = &$sql_prepare[$i];
            }

            if (null !== $bind && !is_array($bind)) {
                $bind = [$bind];
            }

            // in param
            if ($item == '#') {
                $bind_prepare[$k] = isset($bind[0][0]) ? array_shift($bind) : $bind;
                $sql_prepare[$k] = implode(',', array_fill(0, count($bind_prepare[$k]), '?'));
                continue;
            }
            // normal param
            if ($item == '?') {
                $bind_prepare[$k] = array_shift($bind);
                $sql_prepare[$k] = '?';
                continue;
            }

            // normal string
            $sql_prepare[$k] = $item;
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getSql()
    {
        ksort($this->_sql_prepare);
        $sql = '';
        array_walk_recursive($this->_sql_prepare, function($item) use (&$sql) {
            $sql .= $item;
        });
        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        ksort($this->_bind_prepare);
        $bind = [];
        array_walk_recursive($this->_bind_prepare, function($item) use (&$bind) {
            $bind[] = $item;
        });
        return $bind;
    }
}