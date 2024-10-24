<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class NotCached extends ModelData implements TestOnly
{
    public $Test;

    public function objCacheGet(string $fieldName, array $arguments = []): mixed
    {
        // Disable caching
        return null;
    }
}
