<?php

namespace SilverStripe\Assets\Tests\GDTest;

use SilverStripe\Assets\GDBackend;
use SilverStripe\Dev\TestOnly;

class ImageUnavailable extends GDBackend implements TestOnly
{

    public function failedResample($arg = null)
    {
        return true;
    }
}
