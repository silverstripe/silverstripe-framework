<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use Countable;
use SilverStripe\Dev\TestOnly;

class CountableObject implements Countable, TestOnly
{
    public function count(): int
    {
        return 53;
    }
}
