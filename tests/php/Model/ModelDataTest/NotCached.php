<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class NotCached extends ModelData implements TestOnly
{
    public $Test;

    protected function objCacheGet($key)
    {
        // Disable caching
        return null;
    }
}
