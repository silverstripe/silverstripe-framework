<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class EditingAllowedDeletingDeniedExtension extends Extension implements TestOnly
{

    protected function canView($member = null)
    {
        return true;
    }

    protected function canEdit($member = null)
    {
        return true;
    }

    protected function canDelete($member = null)
    {
        return false;
    }
}
