<?php

namespace SilverStripe\Core\Tests\ExtensionTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class NamedExtension extends Extension implements TestOnly
{
    public function getTestValue()
    {
        return 'test';
    }
}
