<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class CreateTest extends BaseObject
{
    public $constructArguments;

    public function __construct()
    {
        $this->constructArguments = func_get_args();
        parent::__construct();
    }
}
