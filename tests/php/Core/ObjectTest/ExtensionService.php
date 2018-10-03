<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtensionService extends Extension implements TestOnly
{
    private $property;

    public function __construct($property)
    {
        $this->property = $property;
    }

    public function aMethod()
    {
        return 'This extension is ' . $this->property;
    }
}
