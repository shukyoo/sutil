<?php
xhprof_enable();

require __DIR__ . '/init.php';

use Sutil\Database\DB;

DB::config(array(
    'driver' => 'mysql',
    'dbname' => 'test',
    'username' => 'root'
));

function a()
{
    DB::beginTransaction();
    try {
        DB::insert('data_test', array(
            'title' => 'tttt1',
            'content' => 'ppppp1',
            'tt' => 100
        ));
        b();
        DB::commit();
    } catch(Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

function b()
{
    DB::beginTransaction();
    try {
        DB::insert('data_test', array(
            'title' => 'tttt2',
            'content' => 'pppp2',
            'tt' => 100
        ));
        //throw new Exception('test');
        DB::commit();
    } catch(Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

DB::beginTransaction();

try {

    DB::insert('data_test', array(
        'title' => 'tttt0',
        'content' => 'ppppp0',
        'tt' => 100
    ));

    a();

    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    echo $e->getMessage();
}



/*
DB::update('data_test', array(
    'tt' => DB::express('tt+1')
), 'id in?', [9,10]);
*/


$xhprof_data = xhprof_disable();
$XHPROF_ROOT = 'E:\www\xhprof';
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
$xhprof_runs = new XHProfRuns_Default();
// save the run under a namespace "xhprof_foo"
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");

echo '<a href="http://localhost/xhprof/xhprof_html/index.php?run='. $run_id .'&source=xhprof_foo">查看xhprof</a>';
