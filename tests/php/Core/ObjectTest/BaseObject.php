<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

class BaseObject implements TestOnly
{
    use Extensible;
    use Configurable;
    use Injectable;

    public function __construct()
    {
    }
}
