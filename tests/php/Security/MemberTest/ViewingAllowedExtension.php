<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class ViewingAllowedExtension extends Extension implements TestOnly
{

    protected function canView($member = null)
    {
        return true;
    }
}
