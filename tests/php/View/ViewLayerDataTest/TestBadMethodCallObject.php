<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use BadMethodCallException;
use SilverStripe\Dev\TestOnly;

class TestBadMethodCallObject implements TestOnly
{
    public function __call(string $name, array $arguments): void
    {
        throw new BadMethodCallException('This exception should be caught by ViewLayerData');
    }
}
