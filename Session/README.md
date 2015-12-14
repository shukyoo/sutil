# Sutile\Session
This is a light session component;

## Install


## Useage

### Config

```PHP
Session::config(array(
    'lifetime' => 1440,
    'handler' => 'cache',
    'connection' => 'memcache'
));
```

### Use
```php
use Sutil\Cache\Cache;
use Sutil\Session\Session;


Session::config(array(
    'lifetime' => '1000',
    'handler' => 'cache',
    'storage' => 'memcache'
));

Session::set('test', 111);
echo Session::get('test', 'none');
```

