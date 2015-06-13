<?php
define('LIB_PATH', dirname(__DIR__));

require LIB_PATH .'/Adapters/AdapterInterface.php';
require LIB_PATH .'/Adapters/Adapter.php';
require LIB_PATH .'/Adapters/Mysql.php';

require LIB_PATH .'/ConnectionInterface.php';
require LIB_PATH .'/Connection.php';
require LIB_PATH .'/QueryInterface.php';
require LIB_PATH .'/Query.php';

require LIB_PATH .'/DB.php';


function p($var)
{
    echo '<pre>';
    print_r($var);
    exit;
}
