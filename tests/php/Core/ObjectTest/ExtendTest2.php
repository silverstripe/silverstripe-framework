<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtendTest2 extends Extension implements TestOnly
{
    protected $constructorArgs = [];

    public function __construct()
    {
        parent::__construct();
        $this->constructorArgs = func_get_args();
    }

    public function extendableMethod($argument = null)
    {
        $args = implode(',', array_filter(func_get_args()));
        return "ExtendTest2($args)";
    }
}
