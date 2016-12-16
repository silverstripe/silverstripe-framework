<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class NeedsBothCirculars implements TestOnly
{

    public $circularOne;
    public $circularTwo;
    public $var;
}
