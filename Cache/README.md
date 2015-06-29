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
    'default' => 'memcache',
    'memcache' => array(
        'host' => 'localhost', 'port' => 11211, 'weight' => 1
    ),
    'redis' => array(
        'host' => 'localhost', 'port' => 6379, 'auth' => 'xxxx'
    )
));

// redis with cluster
Cache::config(array(
    'default' => 'redis',
    'redis' => array(
        'cluster' => 'mycluster'
    )
));
Cache::config(array(
    'default' => 'redis',
    'redis' => array(
        'cluster' => array(
            'localhost:7000', 'localhost2:7001', 'localhost:7002'
        )
    )
));
```


