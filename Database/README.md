# Sutile\Database
This is a light database component based on PDO;
It support multiple database;
It support multiple master/slave mode;
No ORM, Just SQL query, simple is beautiful;


## Install


## Useage

### Init Config

```PHP
// Config
DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test'
));
```
[More about config](#config)

### Fetch All
```php
// Fetch all
$sql = 'SELECT * FROM users WHERE gender=? ORDER BY id DESC';
$data = DB::fetchAll($sql, 'male');

// Fetch all indexed by first column
$sql = 'SELECT id, username, age FROM users WHERE gender=? ORDER BY id DESC';
$data = DB::fetchAllIndexed($sql, 'male');

// Fetch all grouped by first column
$sql = 'SELECT gender, id, username, age FROM users WHERE age>=? AND is_active=?';
$data = DB::fetchAllGrouped($sql, [20, 1]);

// Fetch all and set into class and return array of classes
$sql = 'SELECT * FROM users WHERE gender=:gender';
$users = DB::fetchAllClass('User', $sql, 'female');
```

### Fetch Row
```php
// Fetch one row
$sql = 'SELECT * FROM users WHERE id=?';
$data = DB::fetchRow($sql, 1);

// Fetch one row into class and return
$sql = 'SELECT * FROM users WHERE id=:id';
$user = DB::fetchRowClass('User', $sql, [':id' => 1]);
```

### Fetch Columns
```php
$sql = 'SELECT name FROM users LIMIT 10';
$data = DB::fetchCol($sql);
```

### Fetch first/second column as K/V pairs
```php
// Fetch K/V pairs array
$sql = 'SELECT id, name FROM users';
$data = DB::fetchPairs($sql);

// Fetch grouped by first column and pairs second/third columns
$sql = 'SELECT gender, id, name FROM users';
$data = DB::fetchPairsGrouped($sql);

```

### Fetch one
```php
$sql = 'SELECT name FROM users WHERE id=1';
$name = DB::fetchOne($sql);
```

### Insert
```php
DB::insert('users', array(
    'name' => 'my name',
    'gender' => 'male',
    'age' => 28
));
```

### Update
```php
DB::update('users', ['name' => 'hello'], array(
    'id' => 1
));

// with increment
DB::update('users', array(
    'name' => 'hello',
    'age' => DB::express('age+1')
), 'id=1');
```

### Delete
```php
DB::delete('users', 'id=?', 1);
```

[More about where clause](#where-clause)



### Transaction
```php
// In closure
DB::transaction(function($query){
    $query->insert(...);
    $query->update(...);
});


// Begin and commit
$query = DB::getQuery();
$query->beginTransaction();
try {
    $query->insert(...);
    $query->update(...);
    $query->commit();
} catch(Exception $e) {
    $query->rollBack();
    throw $e;
}


// Nest Transaction
DB::beginTransaction();
try {
    
    DB::transaction(function($query){
        
        $query->transaction(function($query){
            ....
        });

    });

} catch (Exception $e) {
    DB::rollBack();
}

```

### connect other database
```php
DB::connect('db2')->fetchAll($sql);
DB::connect('db3')->insert(...);
```



## Config

### Mysql config keys
|  Key  | Value |
| ------------- | ------------- |
| driver | *required |
| host  | 127.0.0.1 (default)  |
| dbname | *required |
| username | [optional] |
| password | [optional] |
| charset  | utf8 (default)  |
| options | [optional] (array of PDO options) |
 

### single database
```php
DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test',
    'username' => 'dev',
    'password' => 'dev123',
    'options' => [PDO::ATTR_PERSISTENT => true]
));
```

### multiple database
```php
DB::config(array(
    'db1' => [
        'driver' => 'mysql',
        'dbname' => 'test1'
    ],
    'db2' => [
        'driver' => 'mysql',
        'dbname' => 'test2'
    ]
));
```

### single database with master/slave mode
```php
// 1master 1slave
DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test',
    'slave' => array(
        'dbname' => 'ttt',
        'username' => 'dev'
    )
));

// 1master multiple slaves
DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test',
    'slaves' => array(
        ['dbname' => 'ss1', 'username' => 'dev1'],
        ['dbname' => 'ss2', 'username' => 'dev2']
    )
));
```

### multiple masters and multiple slaves
```php
DB::config(array(
    'driver' => 'mysql',
    'masters' => array(
        ['dbname' => 'ss1', 'username' => 'dev1'],
        ['dbname' => 'ss2', 'username' => 'dev2']
    ),
    'slaves' => array(
        ['dbname' => 'ss1', 'username' => 'dev1'],
        ['dbname' => 'ss2', 'username' => 'dev2']
    )
));
```

### multiple database with master/slave mode
```php
DB::config(
    'default' => 'db1',
    'db1' => array(
        'driver' => 'mysql',
        'dbname' => 'test1'
        'slave' => [....]
    ),
    'db2' => array(
        'driver' => 'mysql',
        'dbname' => 'test2',
        'slaves' => array(
            [...], [...]
        )
    )
);
```


## Where Clause
=?, >=?, <=?, !=?
like?, llike?, rlike?
in?, notin?
between?

['a'=>1, 'b'=>2] // and
['a'=>1, 'or b'=>2] // or
[['a'=>1, 'or b'=>2], ['c'=>3, 'or d'=>4]] // group and
['a'=>1, 'or'=>['b'=>2, 'c'=>3]] // group or

### examples
```php
DB::update('users', [], 'id=1');

DB::update('users', [], 'id=?', 1);

DB::update('users', [], 'id in?', [1,2,3]);

DB::update('users', [], 'id=:id', [':id' => 1]);

DB::update('users', [], array(
    'id' => 1
));

DB::update('users', [], array(
    'name like?' => 'test',
    'age>=?' => 20
));

DB::update('users', [], array(
    'name llike?' => 'test'
));

DB::update('users', [], array(
    'name rlike?' => 'test'
));

DB::update('users', [], array(
    'age in?' => [22, 23, 24]
));

DB::update('users', [], array(
    'age notin?' => [22, 23, 24]
));

DB::update('users', [], array(
    'age between?' => [22, 30]
));

DB::update('users', [], array(
    'id=?' => 1, 'or id=?' => 2
));

DB::update('users', [], array(
    'id=?' => 1, 'or' => ['gender' => 'male', 'age<?' => 10]
));

DB::update('users', [], array(
    ['age>?' => 20, 'or gender' => 'male'],
    ['age<?' => 10, 'or gender!=?' => 'female']
));

DB::update('users', [], array(
    'age in?' => function($query){
        $sql = 'SELECT age FROM test WHERE xx="xx"';
        return $query->fetchCol($sql);
    }
));

DB::update('users', [], function(){
    return 'id=1';
});
```


DB::fetchOne($sql);
DB::execute($sql);
DB::table('article')->insert($data);
DB::table('article')->where('id', 1)->update($data);
DB::table('article')->where()->fetchAll();

$sql = 'SELECT id, name FROM users WHERE cid=?' . ' AND bid=?' . 'ORDER BY id DESC'. LIMIT 0, 100;



## Query Usage
```php
$data = DB::query('article')->where(['user_id' => 3])->fetchAll();
DB::query('article')->delete(['id' => 3]);
DB::query('article')->insert(['title' => 'aaaaa', 'content' => 'bbbbb']);
DB::query('article')->save(['title' => 'aaaaa33', 'content' => 'bbbb33'], ['id' => 13]);

$data = DB::query('select * from article where user_id=?', 3)->fetchAll();
$data = DB::query('select * from article where id in(#)', [1,2,3])->fetchAll();
```