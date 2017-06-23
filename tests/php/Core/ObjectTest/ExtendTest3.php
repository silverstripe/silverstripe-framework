<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtendTest3 extends Extension implements TestOnly
{
    protected $constructorArgs = [];

    public function __construct()
    {
        parent::__construct();
        $this->constructorArgs = func_get_args();
    }

    public function extendableMethod($argument = null)
    {
        return "ExtendTest3($argument)";
    }
}
