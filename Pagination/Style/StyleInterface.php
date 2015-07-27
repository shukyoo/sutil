<?php namespace Sutil\Pagination\Style;

use Sutil\Pagination\Paginator;

interface StyleInterface
{
    /**
     * @param Paginator $page_range
     * @return array
     */
    public function getRange(Paginator $paginator);
}