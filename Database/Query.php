<?php namespace Sutil\Database;

use Closure;
use Sutil\Database\ConnectionInterface;
use PDO;

class Query implements QueryInterface
{
    protected $_connection = null;

    public function __construct(ConnectionInterface $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($sql, $bind = null)
    {
        $stmt = $this->_connection->prepare($sql);
        $stmt->execute($bind);
        return $stmt;
    }

    /**
     * {@inheritDoc}
     */
    public function query($sql, $bind = null)
    {
        $stmt = $this->_connection->prepare($sql);
        return $stmt->execute($bind);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId()
    {
        return $this->_connection->getPDO()->lastInsertId();
    }


    /**
     * {@inheritDoc}
     */
    public function fetchAll($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchAllIndexed($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchAllGrouped($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchAllClass($class, $sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchRow($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchRowClass($class, $sql, $bind = null)
    {
        $stmt = $this->prepare($sql, $bind);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
        return $stmt->fetch();
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchCol($sql, $bind)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchPairs($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchPairsGrouped($sql, $bind = null)
    {
        $data = [];
        foreach ($this->prepare($sql, $bind)->fetchAll(PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchOne($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchColumn(0);
    }

    
    /**
     * {@inheritDoc}
     */
    public function insert($table, $data)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->_quoteIdentifier($col);
            $vals[] = '?';
        }
        $sql = "INSERT INTO " . $this->_quoteIdentifier($table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this->query($sql, array_values($data));
    }
    
    /**
     * {@inheritDoc}
     */
    public function update($table, $data, $where = null)
    {
        $set = [];
        foreach ($data as $col => $val) {
            $val = '?';
            $set[] = $this->quoteIdentifier($col) . ' = ' . $val;
        }
        $where = $this->where($where);
        $sql = 'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . implode(', ', $set) . (($where) ? " WHERE $where" : '');
        return $this->query($sql, array_values($data));
    }

    /**
     * {@inheritDoc}
     */
    public function save($table, $data, $where = null)
    {
        $where = $this->where($where);
        $sql = 'SELECT COUNT(*) FROM ' . $this->_quoteIdentifier($table) . (($where) ? " WHERE $where" : '');
        if ($this->fetchOne($sql)) {
            return $this->update($table, $data, $where);
        } else {
            return $this->insert($table, $data);
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function delete($table, $where = null)
    {
        $where = $this->where($where);
        $sql = 'DELETE FROM ' . $this->_quoteIdentifier($table) . (($where) ? " WHERE $where" : '');
        return $this->query($sql);
    }
    
    /**
     * {@inheritDoc}
     */
    public function increment($table, $field, $amount = 1)
    {
        $sql = 'UPDATE ';
    }
    
    /**
     * {@inheritDoc}
     */
    public function decrement($table, $field, $amount = 1)
    {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function transaction(Closure $callback)
    {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        
    }


    /**
     * where clause, in chain type
     * where('user_id=?', 2)
     * where(['user_id=?' => 2, 'user_name=?' => 'test', 'age>=?' => 12])
     * where('user_id in?', [1,2,3])
     * where('user_id notin?', [1,2,3])
     * where('user_id between?', [1, 5])
     * where('user_id is null')
     * where('user_id is not null')
     * where('user_id=?', function(){
     *     return 2;
     * })
     * where(function(){
     *     return ['user_id=?' => 2, 'user_name' => 'test'];
     * });
     * where(['id=?' => 1, 'or' => ['id' => 2]])
     * where([['id=?' => 1, 'name=?' => 'test'], ['id=?' => 2, 'name=?' => 'ttt']])
     * where(['id=?' => 1, ['name' => 'test']])
     *
     * @param string|array $set
     * @param mixed $value
     * @return $this
     */
    protected function _where($cond, $value = null, &$bind = [])
    {
        if (!is_array($cond) && null === $value) {
            return $cond;
        }
        if (is_string($cond)) {
            return $this->_wherePart($cond, $value, $bind);
        }
        $where_str = '';

    }

    /**
     * ('id', 1)
     * ('id=?', 1)
     * ('id in?', [1,2])
     * in, notin, between, like, llike, rlike
     */
    protected function _wherePart($cond, $value, &$bind = [])
    {
        strpos($cond, '?') || $cond = "{$cond}=?";
        preg_match('/(\w+)(\s+[\w]+|\s*[!=><]+)\s*\?\s*/', $cond, $matches);
        if (empty($matches[2])) {
            throw new \Exception('Invalid where condition');
        }
        $field = $this->_quoteIdentifier($matches[1]);
        $opt = trim($matches[2]);
        $part_str = $field;
        switch ($opt) {
            case 'in':
                $part_str .= " IN({$this->_inExp($value, $bind)})";
                break;
            case 'notin':
                $part_str .= " NOT IN({$this->_inExp($value, $bind)})";
                break;
            case 'between':
                $bind = array_merge($bind, $value);
                $part_str .= " BETWEEN ? AND ?";
                break;
            case 'like':
                $bind[] = "%{$value}%";
                $part_str .= ' LIKE ?';
                break;
            case 'llike':
                $bind[] = "%{$value}";
                $part_str .= ' LIKE ?';
                break;
            case 'rlike':
                $bind[] = "{$value}%";
                $part_str .= ' LIKE ?';
                break;
            default:
                $bind[] = $value;
                $part_str .= "{$opt}?";
                break;
        }
        return $part_str;
    }

    protected function _inExp($data, &$bind = [])
    {
        if (is_string($data)) {
            $data = explode(',', $data);
        }
        $str = '';
        foreach ($data as $v) {
            $str .= '?,';
            $bind[] = $v;
        }
        return trim($str, ',');
    }


    protected function _quoteIdentifier($identifier)
    {
        return $this->_connection->quoteIdentifier($identifier);
    }
}