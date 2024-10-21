<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtensibleObjectExtension extends Extension implements TestOnly
{
    public function getIterator(): iterable
    {
        return ['1','2','3','4','5','6','7','8','9','a','b','c','d','e'];
    }

    public function getAnything(): string
    {
        return 'something';
    }

    public function forTemplate(): string
    {
        return 'This text comes from the extension class';
    }
}
