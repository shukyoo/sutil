<?php namespace Sutil\Database;

use Closure;
use PDO;

class Query implements QueryInterface
{
    protected $connection = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \PDOStatement
     */
    protected function prepare($sql, $bind = null)
    {

    }


    /**
     * $sql = 'SELECT * FROM users WHERE gender=?';
     * fetchAll($sql, ['gender' => 'boy']);
     * 
     * @param string $sql
     * @param array $bind
     * @return array with nature index, empty array returned if nothing or false
     */
    public function fetchAll($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * The first field will be the indexed key, recommend
     * $sql = 'SELECT id, name, gender FROM users WHERE gender=?';
     * fetchAllIndexed($sql, ['gender' => 'boy'])
     *
     * @param string $sql
     * @param array $bind
     * @return array fetch all with specified index, empty array returned if nothing or false
     */
    public function fetchAllIndexed($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }
    
    /**
     * The first key will be the keys of group, recommend
     * $sql = 'SELECT age, id, name FROM users WHERE gender=?';
     * fetchAllGrouped($sql, ['gender' => 'boy'])
     *
     * @param string $sql
     * @param array $bind
     * @return array fetch all grouped with specified field, empty array returned if nothing or false
     */
    public function fetchAllGrouped($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }
    
    /**
     * $sql = 'SELECT * FROM users WHERE gender=?';
     * fetchAllClass('User', $sql, ['gender' => 'boy'])
     * 
     * @param string|object $class
     * @param string $sql
     * @param array $bind
     * @return array return array of classes, empty array returned if nothing or false
     */
    public function fetchAllClass($class, $sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
    }
    
    /**
     * $sql = 'SELECT * FROM users WHERE id=?';
     * fetchRow($sql, ['id' => 1])
     * 
     * @param string $sql
     * @param array $bind
     * @return array one row, empty array returned if nothing or false
     */
    public function fetchRow($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * $sql = 'SELECT * FROM users WHERE id=?';
     * fetchRowClass('User', $sql, ['id' => 1])
     * 
     * @param string|object $class
     * @param string $sql
     * @param array $bind
     * @return object|null return instance of the class, null returned if nothing or false
     */
    public function fetchRowClass($class, $sql, $bind = null)
    {
        $stmt = $this->prepare($sql, $bind);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
        return $stmt->fetch();
    }
    
    /**
     * $sql = 'SELECT name FROM users WHERE gender=?';
     * fetchCol($sql, ['gender' => 'boy']);
     * 
     * @param string $sql
     * @param array $bind
     * @return array return first column array, empty array returned if nothing or false
     */
    public function fetchCol($sql, $bind)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * $sql = 'SELECT id, name FROM users WHERE gender=?';
     * fetchPairs($sql, ['gender' => 'boy'])
     * 
     * @param string $sql
     * @param array $bind
     * @return array return pairs of first column as Key and second column as Value, empty array returned if nothing or false
     */
    public function fetchPairs($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * The first field is the keys of group
     * $sql = 'SELECT age, id, name FROM users WHERE gender=?';
     * fetchPairsGrouped($sql, ['gender' => 'boy'])
     * 
     * @param string $group_field
     * @param string $sql
     * @param array $bind
     * @return array return grouped pairs of K/V with specified field, empty array returned if nothing of false
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
     * $sql = 'SELECT name FROM users WHERE id=?';
     * fetchOne($sql, ['id' => 1])
     * 
     * @param string $sql
     * @param array $bind
     * @return mixed return one column value, false returned if nothing or false
     */
    public function fetchOne($sql, $bind = null)
    {
        return $this->prepare($sql, $bind)->fetchColumn(0);
    }
    
    /**
     * Run SQL
     * @param string $sql
     * @param array $bind
     * @return mixed
     */
    public function query($sql, $bind = null)
    {
        return $this->prepare($sql, $bind);
    }
    
    /**
     * Insert
     * @param string $table
     * @param array $data
     * @return boolean
     */
    public function insert($table, $data)
    {
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->quoteIdentifier($col);
            $vals[] = '?';
        }
        $sql = "INSERT INTO " . $this->quoteIdentifier($table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        return $this->query($sql, array_values($data));
    }
    
    /**
     * Get last insert id
     * @return int|string
     */
    public function lastInsertId()
    {
        
    }
    
    /**
     * Update
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @return boolean
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
     * Save
     * Update if exists, or insert
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @return boolean
     */
    public function save($table, $data, $where = null)
    {
        $where = $this->where($where);
        $sql = 'SELECT COUNT(*) FROM ' . $this->quoteIdentifier($table) . (($where) ? " WHERE $where" : '');
        if ($this->fetchOne($sql)) {
            return $this->update($table, $data, $where);
        } else {
            return $this->insert($table, $data);
        }
    }
    
    /**
     * Delete
     * @param string $table
     * @param mixed $where
     * @return boolean
     */
    public function delete($table, $where = null)
    {
        $where = $this->where($where);
        $sql = 'DELETE FROM ' . $this->quoteIdentifier($table) . (($where) ? " WHERE $where" : '');
        return $this->query($sql);
    }
    
    /**
     * Increment
     * @param string $table
     * @param string $field
     * @param int $amount
     * @return boolean
     */
    public function increment($table, $field, $amount = 1)
    {
        $sql = 'UPDATE ';
    }
    
    /**
     * Decrement
     * @param string $table
     * @param string $field
     * @param int $amount
     * @return boolean
     */
    public function decrement($table, $field, $amount = 1)
    {
        
    }
    
    /**
     * Execute a Closure within a transaction.
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(Closure $callback)
    {
        
    }
    
    /**
     * Start a new database transaction.
     * @return void
     */
    public function beginTransaction()
    {
        
    }
    
    /**
     * Commit the active database transaction.
     * @return void
     */
    public function commit()
    {
        
    }
    
    /**
     * Rollback the active database transaction.
     * @return void
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
    public function where($set, $value = null)
    {
        
    }
}