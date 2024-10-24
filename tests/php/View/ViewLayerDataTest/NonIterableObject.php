<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use SilverStripe\Dev\TestOnly;

class NonIterableObject implements TestOnly
{
    public function getIterator(): iterable
    {
        return [
            'some value',
            'another value',
            'isnt this nice',
        ];
    }
}
