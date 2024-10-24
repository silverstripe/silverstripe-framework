<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use SilverStripe\Dev\TestOnly;

class GetCountObject implements TestOnly
{
    public function getCount(): int
    {
        return 12;
    }
}
