<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class NotCached extends ViewableData implements TestOnly
{
    public $Test;

    protected function objCacheGet($key)
    {
        // Disable caching
        return null;
    }
}
