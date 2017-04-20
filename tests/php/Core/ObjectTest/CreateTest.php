<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class CreateTest extends Object
{
    public $constructArguments;

    public function __construct()
    {
        $this->constructArguments = func_get_args();
        parent::__construct();
    }
}
