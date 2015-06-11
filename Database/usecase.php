<?php
$sql = 'SELECT * FROM users WHERE gender="boy"';
$db->fetchAll($sql);

$sql = 'SELECT * FROM users WHERE gender=?';
$db->fetchAllIndexed('id', $sql, ['gender' => 'boy']);

$sql = 'SELECT * FROM users';
$db->fetchAllGrouped('gender', $sql);
$db->fetchAllClass('User', $sql);

$sql = 'SELECT id, username FROM users';
$db->fetchPairs($sql);

$sql = 'SELECT gender, id, username FROM users';
$db->fetchPairsGrouped('gender', $sql);

$sql = 'SELECT * FROM users WHERE id=?';
$db->fetchRow($sql, ['id' => 1]);
$db->fetchRowClass('User', $sql, ['id' => 2]);



$sql = 'INSERT INTO users(username, realname, gender) VALUES("test", "real test", "boy")';
$db->query($sql);

$db->insert('users', array(
    'username' => 'test',
    'realname' => 'real test',
    'gender' => 'boy'
));

$db->update('users', array(
    'username' => 'test',
    'realname' => 'real name'
), array(
    'user_id=?' => 2
));
$db->update('users', function($db){
    return ['username' => 'test', 'realname' => 'real name'];
}, function($db){
    return ['id=?' => 1];
});
$db->where('id=?', 1)->update('users', [
    'username' => 'test',
    'realname' => 'real name'
]);

$sql = 'DELETE FROM users WHERE user_id=2';
$db->execute($sql);

$db->delete('users', array(
    'user_id' => 2
));

$db->increment('users', 'age', 1);
$db->decrement('users', 'age', 1);
$db->increment('users', ['age' => 1, 'point' => 2]);

$db->transaction(function($query){
    $query->insert();
    $query->update();
});


$db->beginTransaction();
$db->commit();
$db->rollBack();


/*
 * where('user_id=?', 2)
 * where(['user_id=?' => 2, 'user_name=?' => 'test'])
 * where('user_id in?', [1,2,3])
 * where('user_id notin?', [1,2,3])
 * where('user_id between?', [1, 5])
 * where('user_id is null')
 * where('user_id is not null')
 * orWhere('user_id', 2)
 * orWhere('user_id is not null')
 * ...
 * whereGroup($nested)
 * orWhereGroup($nested)
 * 
*/


// get column names
$q = $dbh->prepare("DESCRIBE tablename");
$q->execute();
$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);


// schema
// select column_name from information_schema.columns where table_schema = ? and table_name = ?
// select column_name from information_schema.columns where table_schema = ? and table_name = ?


/**
 * DB::config(array(
 *     'driver' => 'mysql',
 *     'database' => 'test'
 *     ......
 * ));
 */

/**
 * $db = new Manager($config);
 * $db->fetchAll($sql);
 */

/**
 * primary database
 * DB::fetchAll($sql);
 *
 * Multi database
 * DB::connect('mysql2')->fetchAll($sql);
 *
 * Recommend use model, it's easier for separate and reset connection
 * You should make cache tactic in the model
 */

/**
 * Model Usecase
 */
class User extends Model
{
}

User::getBoys();
User::reg([
    'name' => 'test',
    'age' => 12
]);
User::get(1);
User::getNameLike('mike');



Query::fetchAll();
Schema::createTable();