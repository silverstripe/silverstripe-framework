<?php

namespace SilverStripe\Core\Test\Cache\CacheTest;

use SilverStripe\Dev\TestOnly;

class MockCache implements TestOnly
{
    protected $args = [];

    public function __construct()
    {
        $this->args = func_get_args();
    }

    /**
     * Get constructor args
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }
}
