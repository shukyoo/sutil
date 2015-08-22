<?php namespace Sutil\Database\Query;

use Sutil\Database\ConnectionInterface;


class Table extends QueryAbstract
{
    protected $_table;

    protected $_bind = [];

    protected $_selection = '*';

    protected $_where = '';


    public function __construct(ConnectionInterface $connection, $table, $where_cond = null, $where_value = null)
    {
        $this->_connection = $connection;
        $this->_table = trim($table);
        if (null !== $where_cond) {
            $this->_where = $this->_whereParse($where_cond, $where_value);
        }
    }

    /**
     * @param string|array $selection
     */
    public function select($selection)
    {
        if (is_string($selection)) {
            $selection = func_num_args() == 1 ? explode(',', $selection) : func_get_args();
        }
        foreach ($selection as $k => $field) {
            $field = trim($field);
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



    protected function _table()
    {
        return $this->_quoteIdentifier($this->_table);
    }

    protected function _where()
    {
        return $this->_where ? " WHERE {$this->_where}" : '';
    }


    /**
     * Get count of records
     * @return int
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM '. $this->_table() . $this->_where();
        return $this->_connection->select($sql, $this->_bind)->fetchColumn(0);
    }

    /**
     * Check if record exists
     * @return bool
     */
    public function exists()
    {
        return (bool)$this->count();
    }

    /**
     * Insert data
     * @param $data
     * @return bool
     */
    public function insert($data)
    {
        is_callable($data) && $data = $data();
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $sql = 'INSERT INTO ' . $this->_table() .' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this->_connection->execute($sql, array_values($data));
    }

    /**
     * Update data
     * @param $data
     * @return bool
     */
    public function update($data)
    {
        is_callable($data) && $data = $data();
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
        $bind = array_merge(array_values($data), $this->_bind);
        $sql = "UPDATE {$this->_table()} SET ". implode(', ', $set) . $this->_where();
        return $this->_connection->execute($sql, $bind);
    }

    /**
     * Update if exists otherwise insert
     * @return bool
     */
    public function save($data)
    {
        if ($this->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * Delete data
     * @return bool
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->_table()}{$this->_where()}";
        return $this->_connection->execute($sql, $this->_bind);
    }



    /**
     * where clause
     * where('user_id=?', 2)
     * where(['user_id=?' => 2, 'user_name=?' => 'test', 'age>=?' => 12])
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
     * @param mixed $cond
     * @param mixed $value
     * @param array &$bind
     * @param array $where_bind [simple mode] elements count should be equal with ? count in $cond
     * @return Table
     */
    public function where($cond, $value = null, $co = 'AND')
    {
        $co = trim($this->_where) ? " {$co} " : '';
        $this->_where .= $co . $this->_whereParse($cond, $value);
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
    protected function _whereParse($cond, $value = null)
    {
        if (is_string($cond)) {
            $cond = trim($cond);
            return (null === $value) ? $cond : $this->_wherePart($cond, $value);
        }
        $where_str = '';
        foreach ($cond as $key => $value) {
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
     * ('id in?', [1,2])
     * in, notin, between, like, llike, rlike
     */
    protected function _wherePart($cond, $value)
    {
        if (!strpos($cond, '?')) {
            $field = trim($cond);
            $opt = is_array($value) ? 'in' : '=';
        } else {
            preg_match('/^(\w+)(\s+[\w]+|\s*[!=><]+)\s*\?$/', $cond, $matches);
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
        return "SELECT {$this->_selection} FROM {$this->_table()}{$this->_where()}{$this->_group()}{$this->_order()}{$this->_limit()}";
    }


    /**
     * {@inheritDoc}
     */
    public function getBind()
    {
        return $this->_bind;
    }
}