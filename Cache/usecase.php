<?php
$cache->setExpire(1200);
$cache->set('test', [1,2,3]);
$cache->set('hello', 'world', 600);

$cache->get('test');

$cache->del('test');


// Advanced
$cache->set('test', function(){
    return [1,2,3];
}, 1200);

$cache->get('test', function(){
    return 'content';
}, 600);
