<?php namespace Sutil\Database\Query;

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
 */
class Where
{
    /**
     * @param mixed $cond
     * @param mixed $value
     * @param array &$bind
     * @param array $where_bind [simple mode] elements count should be equal with ? count in $cond
     * @return string
     */
    public function where($cond, $value = null, $co = 'AND')
    {
        $this->_where .= " {$co} " . $this->_whereParse($cond, $value);
        return $this;
    }

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
        $field = $this->_connection->quoteIdentifier($field);
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
}