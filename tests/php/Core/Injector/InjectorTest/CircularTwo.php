<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class CircularTwo implements TestOnly
{

    public $circularOne;

    public $otherVar;

    public function __construct($value = null)
    {
        $this->otherVar = $value;
    }
}
