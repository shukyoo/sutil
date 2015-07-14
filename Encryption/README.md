```php
TDES::config(['key'=>'123412341234123412341234123412341234123412341289', 'iv' => '1234567887654321']);
echo TDES::encrypt('test');
echo '<br>';
echo TDES::decrypt('oRLJ76kNPZwJo');
```