<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class CircularOne implements TestOnly
{

    public $circularTwo;
}
