<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Dev\TestOnly;

class BaseObject implements TestOnly
{
    use Configurable;
    use Extensible;
}
