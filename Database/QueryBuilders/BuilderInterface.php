<?php namespace Sutil\Database\QueryBuilders;

interface BuilderInterface
{
    /**
     * Get final SQL string
     * @return mixed
     */
    public function getSql();

    /**
     * Get final bind
     * @return array
     */
    public function getBind();
}