<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;

class Table extends QueryAbstract
{
    /**
     * @var Grammars\GrammarBase
     */
    protected $_grammar;
    protected $_table;
    protected $_bind = [];

    protected $_distinct = false;
    protected $_selection = '*';
    protected $_where = '';
    protected $_group = [];
    protected $_order = [];
    protected $_limit;
    protected $_offset;

    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->_connection = $connection;
        $this->_table = trim($table);

        $driver = $connection->driver();
        $grammar = '\\Sutil\\Database\\Query\\Grammars\\'. ucfirst($driver);
        if (!class_exists($grammar)) {
            $grammar = '\\Sutil\\Database\\Query\\Grammars\\GrammarBase';
        }
        $this->_grammar = new $grammar();
    }

    /**
     * @return $this;
     */
    public function distinct()
    {
        $this->_distinct = true;
        return $this;
    }


    /**
     * @param array|string $selection
     * @return $this
     */
    public function select($selection)
    {
        $this->_selection = $selection;
        return $this;
    }

    /**
     * Group part
     * @param string $field
     * @param string $having
     * @return $this
     */
    public function group($field, $having = null)
    {
        $this->_group[] = $field;
        if ($having) {
            $this->_group[] = $having;
        }
        return $this;
    }

    /**
     * orderBy('id DESC, test ASC')
     * orderBy('id', 'DESC')
     * orderBy(['id' => 'DESC', 'test' => 'ASC'])
     * @param string|array $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = null)
    {
        if (is_array($field)) {
            $this->_order = array_merge($this->_order, $field);
        } elseif ($direction) {
            $this->_order[$field] = $direction;
        } else {
            $this->_order[] = $field;
        }
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderASC($field)
    {
        return $this->orderBy($field, 'ASC');
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderDESC($field)
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Set limit and offset
     * @param int $number
     * @param int $offset
     * @return $this
     */
    public function limit($number, $offset = null)
    {
        $this->_limit = (int)$number;
        if (null !== $offset) {
            $this->_offset = (int)$offset;
        }
        return $this;
    }

    /**
     * Set limit and offset by page
     * @param int $page
     * @param int $page_size
     * @return $this
     */
    public function page($page, $page_size = 20)
    {
        if ($page < 1) {
            $page = 1;
        }
        return $this->limit($page_size, (($page - 1) * $page_size));
    }


    /**
     * where clause
     * where('id=2 and name like "%test%"')
     * where('id=? and name like ?', [2, "test"])
     * where(['id=? and name like "%?%"' => [2, 'test'])
     * where(['id in? and name="test"' => [1,2,3]])
     * where(['id in? or name="?"' => [[1,2,3], 'test']])
     *
     * where(['id' => 1, 'name like ?' => 'test'])
     * where(['id' => [1,2,3], 'or' => ['xxxxxx']])
     *
     * @param string $where
     * @param mixed $bind
     * @return $this
     */
    public function where($where, $bind = null, $co = 'AND')
    {
        if (!$this->_where) {
            $co = '';
        }
        $this->_where .= " {$co} {$this->_where($where, $bind)}";
        return $this;
    }

    /**
     * @param $where
     * @param mixed $bind
     * @return $this
     */
    public function orWhere($where, $bind = null)
    {
        return $this->where($where, $bind, 'OR');
    }

    /**
     * @param mixed $where
     * @param mixed $value
     * @return string
     */
    protected function _where($where, $value = null)
    {
        if (!is_array($where)) {
            return (null === $value) ? trim($where) : $this->_wherePart($where, $value);
        }
        $where_str = '';
        foreach ($where as $k => $v) {
            $jc = is_int($k) ? 'AND' : strtoupper(trim($k));
            if ($jc == 'AND' || $jc == 'OR') {
                $co = $where_str ? " {$jc} " : '';
                $where_str .= $co . $this->_where($v);
            } else {
                $co = $where_str ? " AND " : '';
                $where_str .= $co . $this->_wherePart($k, $v);
            }
        }
        return $where_str;
    }

    /**
     * (id=? and name=?, [1, 'test'])
     * ('id', 1)
     * ('id', [1,2,3])
     */
    protected function _wherePart($condition, $value)
    {
        if (!strpos($condition, '?')) {
            if (is_array($value)) {
                return $this->_grammar->wrap($condition) .' IN('. $this->_whereIn($value) .')';
            } else {
                $this->_bind($value);
                return $this->_grammar->wrap($condition) .'=?';
            }
        } elseif (strpos($condition, 'in?')) {
            $parts = preg_split('/(\?|in\?)/', $condition, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $str = '';
            foreach ($parts as $item) {
                if ($item == 'in?') {
                    $str .= 'IN('. $this->_whereIn((isset($value[0][0]) ? array_shift($value) : $value)) .')';
                } elseif ($item == '?') {
                    $this->_bind(array_shift($value));
                    $str .= $item;
                } else {
                    $str .= $item;
                }
            }
            return $str;
        } else {
            $this->_bind($value);
            return $condition;
        }
    }

    /**
     * Parse where in clause
     * @param $value
     * @return string
     */
    protected function _whereIn($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $this->_bind($value);
        return implode(',', array_fill(0, count($value), '?'));
    }


    /**
     * Generate insert sql
     * @param array $data
     * @return string
     */
    public function insert(array $data)
    {
        $sql = $this->_grammar->insert($this->_table, $data, $bind);
        return $this->_connection->execute($sql, $bind);
    }

    /**
     * Generate update sql
     * @param array $data
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function update(array $data, $where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        $sql = $this->_grammar->update($this->_table, $data, $this->_where, $bind);
        $bind = array_merge($bind, $this->_bind);
        return $this->_connection->execute($sql, $bind);
    }

    /**
     * Generate delete sql
     * @param string $where
     * @param mixed $where_bind
     * @param mixed $bind
     * @return string
     */
    public function delete($where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        $sql = $this->_grammar->delete($this->_table, $this->_where);
        return $this->_connection->execute($sql, $this->_bind);
    }

    /**
     * update if exists, otherwise insert
     */
    public function save(array $data, $where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        if ($this->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * Get count sql
     * @return string
     */
    public function count($where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        $sql = $this->_grammar->count($this->_table, $this->_where);
        return $this->_connection->select($sql, $this->_bind)->fetchColumn(0);
    }

    /**
     * check if exists
     * @return bool
     */
    public function exists($where = null)
    {
        return (bool)$this->count($where);
    }


    /**
     * {@inheritDoc}
     */
    public function getSql()
    {
        $sql = $this->_grammar->select($this->_selection, $this->_distinct) . ' '. $this->_grammar->from($this->_table);
        if ($this->_where) {
            $sql .= ' WHERE ' . trim($this->_where);
        }
        if (!empty($this->_group)) {
            $sql .= ' '. $this->_grammar->group($this->_group);
        }
        if (!empty($this->_order)) {
            $sql .= ' '. $this->_grammar->orderBy($this->_order);
        }
        if (null !== $this->_limit) {
            $sql .= ' '. $this->_grammar->limit($this->_limit, $this->_offset);
        }
        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Bind value
     */
    protected function _bind($bind)
    {
        if (is_array($bind)) {
            $this->_bind = array_merge($this->_bind, array_values($bind));
        } else {
            $this->_bind[] = $bind;
        }
    }
}