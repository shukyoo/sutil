<?php namespace Sutil\Database\Query;

interface BuilderInterface
{
    public function getConnection();

    /**
     * Get the final sql
     * @return string
     */
    public function getSql();

    /**
     * Get the final bind
     * @return array
     */
    public function getBind();
}