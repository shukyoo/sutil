<?php namespace Sutil\Pagination\Style;

use Sutil\Pagination\Paginator;

class All implements StyleInterface
{

    public function getRange(Paginator $paginator)
    {
        $pages = [];
        for ($i=1; $i<=$paginator->getPageCount(); $i++) {
            $pages[] = $i;
        }
        return $pages;
    }
}