<?php
xhprof_enable();

require __DIR__ . '/init.php';

use Sutil\Database\DB;

DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test',
    'username' => 'root'
));

$sql = 'select * from data_test';
$data = DB::fetchAll($sql);
p($data);

$sql = 'select id, title from data_test';
//$data = DB::fetchPairs($sql);

/*
$res = DB::insert('data_test', array(
    'title' => '如果测试',
    'content' => 'aaa测试__--bbbb',
    'tt' => 10,
    'create_time' => date('Y-m-d H:i:s')
));*/
// $res = DB::delete('data_test', 'id=?', 8);



$xhprof_data = xhprof_disable();
$XHPROF_ROOT = 'E:\www\xhprof';
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
$xhprof_runs = new XHProfRuns_Default();
// save the run under a namespace "xhprof_foo"
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");

echo '<a href="http://localhost/xhprof/xhprof_html/index.php?run='. $run_id .'&source=xhprof_foo">查看xhprof</a>';
