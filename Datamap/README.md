# Sutile\Datamap



## Install


## Useage

```PHP
class Test extends Datamap
{
    public function fetchData()
    {
        $sql = 'SELECT * FROM data_test WHERE id<10';
        return $this->db()->fetchAll($sql);
    }

    public function fetchD2()
    {
        $sql = 'SELECT * FROM data_test WHERE id=10';
        return $this->db()->fetchRow($sql);
    }

    public static function _getData()
    {
        return self::instance()->fetchData();
    }

    public static function _getD2()
    {
        return self::instance()->fetchD2();
    }
}

class Test2 extends Datamap
{
    public function fetchDd1()
    {
        $sql = 'SELECT * FROM data_test WHERE id<10';
        return $this->db()->fetchAll($sql);
    }

    public function fetchD2()
    {
        $sql = 'SELECT * FROM data_test WHERE id=12';
        return $this->db()->fetchRow($sql);
    }

    public static function _getD2()
    {
        return self::instance()->fetchD2();
    }
}

print_r(Test2::getD2());
echo '<br>';
print_r(Test::getD2());
echo '<br>';
print_r(Test::getData());

```


