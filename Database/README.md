h# Sutile\Database
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
```

### Delete
```php
DB::delete('users', 'id=?', 1);
```

[More about where clause](#where)



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
}

```

## Config

### Mysql config keys
| Default Key  | Default Value |
| ------------- | ------------- |
| driver | *required |
| host  | 127.0.0.1  |
| dbname | *required |
| username | [optional] |
| password | [optional] |
| charset  | utf8  |
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

### multiple database with master/slave mode
```php
DB::config(
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
