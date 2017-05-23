<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\TestOnly;

class BaseObject implements TestOnly
{
    use Configurable;
}
