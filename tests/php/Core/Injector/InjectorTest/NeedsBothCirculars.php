<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class NeedsBothCirculars implements TestOnly
{

    public $circularOne;
    public $circularTwo;
    public $var;
}
