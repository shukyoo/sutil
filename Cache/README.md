# Sutile\Cache
This is a light cache component;
Support memcache, redis or redis cluster

## Install


## Useage

### Init Config

```PHP
// Config memcache / memcached
Cache::config(array(
    'expiration' => 3600, // the default expiration, 0 forever
    'storage' => 'memcache',
    'storage_config' => array(
        'host' => 'localhost', 'port' => 11211, 'weight' => 1
    )
));

// with multi server
Cache::config(array(
    'storage' => 'memcached',
    'storage_config' => array(
        ['host' => 'host1'], ['host' => 'host2', 'port' => 11211], ['host' => 'host3', 'weight' => 10]
    )
));

// Config redis
Cache::config(array(
    'storage' => 'redis',
    'storage_config' => array(
        'host' => 'localhost', 'port' => 6379, 'auth' => 'xxxx'
    )
));

// with cluster
Cache::config(array(
    'storage' => 'redis',
    'storage_config' => array(
        'cluster' => 'mycluster'
    )
));
Cache::config(array(
    'storage' => 'redis',
    'storage_config' => array(
        'cluster' => array(
            'localhost:7000', 'localhost2:7001', 'localhost:7002'
        )
    )
));
```


