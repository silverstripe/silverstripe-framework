<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use SilverStripe\Dev\TestOnly;
use Stringable;

class StringableObject implements Stringable, TestOnly
{
    public function __toString(): string
    {
        return 'This is the string representation';
    }
}
