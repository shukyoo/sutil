<?php namespace Sutil\Datamap;

use Sutil\Cache\BackendInterface;

class Builder
{
    protected $_datamap = null;
    protected $_cache = null;

    public function __construct(Datamap $datamap, BackendInterface $cache)
    {
        $this->_datamap = $datamap;
        $this->_cache = $cache;
    }

    public function __call($method, $args)
    {
        if (strpos($method, 'get') === 0) {
            $scname = substr($method, 3);
            $scmethod = 'fetch'. $scname;
            if (method_exists($this->_datamap, $scmethod)) {

            }
        }
    }

    public function _fromCache()
    {

    }

}
