<?php

namespace SilverStripe\View\Tests\ArrayDataTest;

use SilverStripe\Dev\TestOnly;

class NonEmptyObject implements TestOnly
{
    public $a;
    public $b;
    public static $c = "Cucumber";

    public function __construct()
    {
        $this->a = "Apple";
        $this->b = "Banana";
    }
}
