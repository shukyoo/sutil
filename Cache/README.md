# Sutile\Cache
This is a light cache component;
Support memcache, redis or redis cluster

## Install


## Useage


### Use
```PHP
Cache::set('test', 1, 60);
echo Cache::get('test');
Cache::delete('test');
Cache::flush();

Cache::increment('test', 2);
Cache::decrement('test', 2);

Cache::getData('test', function(){
    return 'hello';
});

```


### Init Config

```PHP
// Config memcache / memcached
Cache::config(array(
    'expiration' => 3600, // the default expiration, 0 forever
    'storage' => 'memcache',
));
```


