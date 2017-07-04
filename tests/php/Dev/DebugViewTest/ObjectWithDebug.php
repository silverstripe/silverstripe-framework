<?php

namespace SilverStripe\Dev\Tests\DebugViewTest;

use SilverStripe\Dev\TestOnly;

class ObjectWithDebug implements TestOnly
{
    public function debug()
    {
        return __CLASS__ . '::debug() custom content';
    }
}
