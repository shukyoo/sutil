<?php namespace Sutil\Database\QueryBuilders;

use Sutil\Database\ConnectionInterface;

class Table
{
    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    protected $_table_name;

    protected $_sql;

    protected $_bind = [];

    protected $_selection = '*';
    protected $_where = '';
    protected $_group = '';
    protected $_order = [];
    protected $_limit;
    protected $_offset;


    public function __construct(ConnectionInterface $connection, $table_name)
    {
        $this->_connection = $connection;
        $this->_table_name = trim($table_name);
    }


    /**
     * select('aa, [SUM(bb)], cc')
     * select(['aa', ['SUM(bb)'], 'cc'])
     * @param string|array $selection
     * @param mixed $where
     * @return $this
     */
    public function select($selection)
    {
        if (is_string($selection)) {
            $selection = explode(',', $selection);
        }
        foreach ($selection as $k => $field) {
            if (is_array($field) && isset($field[0])) {
                $selection[$k] = $field[0];
            } elseif (strpos($field, '[') === 0 && strpos($field, ']')) {
                $selection[$k] = trim($field, '[]');
            } else {
                $selection[$k] = $this->_quoteIdentifier($field);
            }
        }
        $this->_selection = implode(',', $selection);
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
        $this->_group = " GROUP BY {$this->_quoteIdentifier($field)}";
        if ($having) {
            $this->_group .= " HAVING {$having}";
        }
        return $this;
    }

    /**
     * orderBy('id DESC')
     * orderBy(['id' => 'DESC', 'time' => 'ASC'])
     * @param string|array $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = 'ASC')
    {
        if (is_array($field)) {
            foreach ($field as $k=>$v) {
                $this->_order[] = "{$this->_quoteIdentifier($k)} {$v}";
            }
        } else {
            $this->_order[] = "{$this->_quoteIdentifier($field)} {$direction}";
        }
        return $this;
    }

    public function orderASC($field)
    {
        return $this->orderBy($field, 'ASC');
    }

    public function orderDESC($field)
    {
        return $this->orderBy($field, 'DESC');
    }



    /**
     * Set limit part
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
    public function forPage($page, $page_size = 20)
    {
        $this->_limit = (int)$page_size;
        $this->_offset = ($page - 1) * $this->_limit;
        return $this;
    }



    /**
     * Generate insert sql
     * @param array $data
     * @return $this
     */
    public function insert(array $data)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $this->_bind = array_values($data);
        $this->_sql = 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this;
    }

    /**
     * Generate Update sql
     * @param array $data
     * @return $this
     */
    public function update(array $data, $where = null)
    {
        $set = [];
        foreach ($data as $col => $val) {
            if (is_array($val) && isset($val[0])) {
                $val = $val[0];
                unset($data[$col]);
            } else {
                $val = '?';
            }
            $set[] = $this->_quoteIdentifier($col) . ' = ' . $val;
        }
        if (null !== $where) {
            $this->where($where);
        }
        $this->_bind = array_merge(array_values($data), $this->_bind);
        $this->_sql = "UPDATE {$this->_table()} SET ". implode(', ', $set) . $this->_where();
        return $this;
    }


    /**
     * Generate Delete sql
     * @return $this
     */
    public function delete($where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        $this->_sql = "DELETE FROM {$this->_table()}{$this->_where()}";
        return $this;
    }

    /**
     * Generate count sql
     * @param array|string $where
     * @return $this
     */
    public function count($where = null)
    {
        if (null !== $where) {
            $this->where($where);
        }
        $this->_sql = 'SELECT COUNT(*) FROM '. $this->_table() . $this->_where();
        return $this;
    }



    /**
     * where clause
     * where(['user_id' => 2])
     * where(['user_id' => 2, 'user_name=?' => 'test', 'age>=?' => 12])
     * where('user_id in?', [1,2,3])
     * where('user_id notin?', [1,2,3])
     * where('user_id between?', [1, 5])
     * where('user_id is null')
     * where('user_id is not null')
     * where(['id=?' => 1, 'or' => ['id' => 2]])
     * where([['id=?' => 1, 'name=?' => 'test'], ['id=?' => 2, 'name=?' => 'ttt']])
     * where(['id=?' => 1, ['name' => 'test']])
     * where(['id' => 1, 'or id' => 2])
     *
     * @param mixed $condition
     * @param string $co AND | OR
     * @return $this
     */
    public function where($condition, $co = 'AND')
    {
        $co = trim($this->_where) ? " {$co} " : '';
        $this->_where .= $co . $this->_whereParse($condition);
        return $this;
    }

    /**
     * @return Table
     */
    public function orWhere($cond, $value = null)
    {
        return $this->where($cond, $value, 'OR');
    }

    /**
     * Where parse
     */
    protected function _whereParse($condition)
    {
        if (is_string($condition)) {
            return trim($condition);
        }
        $where_str = '';
        foreach ($condition as $key => $value) {
            $jc = strtoupper(trim($key));
            if (is_int($key) && is_array($value)) {
                $jc = 'AND';
            }
            if (in_array($jc, ['OR', 'AND'])) {
                $where_str .= " {$jc} ({$this->_whereParse($value)})";
            } else {
                $co = 'AND';
                if (strpos($jc, 'OR ')) {
                    $co = 'OR';
                    $key = str_ireplace('OR ', '', $key);
                }
                $where_str .= " {$co} {$this->_wherePart(trim($key), $value)}";
            }
        }
        return preg_replace('/^(AND|OR)\s+(.*)$/i', '\\2', trim($where_str));
    }

    /**
     * ('id', 1)
     * ('id=?', 1)
     * ('id', [1,2])
     * ('id in?', [1,2])
     * in, notin, between, like, llike, rlike
     */
    protected function _wherePart($clause, $value)
    {
        if (!strpos($clause, '?')) {
            $field = trim($clause);
            $opt = is_array($value) ? 'in' : '=';
        } else {
            preg_match('/^(\w+)(\s+[\w]+|\s*[!=><]+)\s*\?$/', $clause, $matches);
            if (empty($matches[2])) {
                throw new \Exception('Invalid where clause');
            }
            $field = $matches[1];
            $opt = strtolower(trim($matches[2]));
        }
        $field = $this->_quoteIdentifier($field);
        $part_str = $field;
        switch ($opt) {
            case 'in':
                $part_str .= " IN({$this->_inExp($value)})";
                break;
            case 'notin':
                $part_str .= " NOT IN({$this->_inExp($value)})";
                break;
            case 'between':
                $this->_bind = array_merge($this->_bind, $value);
                $part_str .= " BETWEEN ? AND ?";
                break;
            case 'like':
                $this->_bind[] = "%{$value}%";
                $part_str .= ' LIKE ?';
                break;
            case 'llike':
                $this->_bind[] = "%{$value}";
                $part_str .= ' LIKE ?';
                break;
            case 'rlike':
                $this->_bind[] = "{$value}%";
                $part_str .= ' LIKE ?';
                break;
            default:
                $this->_bind[] = $value;
                $part_str .= "{$opt}?";
                break;
        }
        return $part_str;
    }

    /**
     * Parse in clause
     */
    protected function _inExp($data)
    {
        if (is_string($data)) {
            $data = explode(',', $data);
        }
        $str = '';
        foreach ($data as $v) {
            $str .= '?,';
            $this->_bind[] = $v;
        }
        return trim($str, ',');
    }



    /**
     * {@inheritDoc}
     */
    public function getSql()
    {
        if ($this->_sql) {
            return $this->_sql;
        } else {
            return "SELECT {$this->_selection} FROM {$this->_table()}{$this->_where()}{$this->_group()}{$this->_order()}{$this->_limit()}";
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        return $this->_bind;
    }



    protected function _quoteIdentifier($identifier)
    {
        $adapter = '\\Sutil\\Database\\Adapters\\'. ucfirst($this->_connection->driver());
        return $adapter::quoteIdentifier($identifier);
    }


    protected function _table()
    {
        return $this->_quoteIdentifier($this->_table_name);
    }

    protected function _where()
    {
        return $this->_where ? " WHERE {$this->_where}" : '';
    }

    protected function _group()
    {
        return $this->_group;
    }

    protected function _order()
    {
        return empty($this->_order) ? '' : (' ORDER BY '. implode(',', $this->_order));
    }

    protected function _limit()
    {
        $str = '';
        if (null !== $this->_limit) {
            $str = (null === $this->_offset) ? " LIMIT {$this->_offset}" : " LIMIT {$this->_offset},{$this->_limit}";
        }
        return $str;
    }
}