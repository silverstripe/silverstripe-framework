<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Tests\Config\ConfigTest\ConfigExtension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extensible;

class BaseObject implements TestOnly
{
    use Configurable;

    private static $extensions = [
        ConfigExtension::class
    ];
}
