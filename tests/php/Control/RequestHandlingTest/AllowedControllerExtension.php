<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Simple extension for the test controller - with allowed_actions define
 */
class AllowedControllerExtension extends Extension implements TestOnly
{
    private static $allowed_actions = array(
        'otherExtendedMethod'
    );

    public function otherExtendedMethod()
    {
        return "otherExtendedMethod";
    }
}
