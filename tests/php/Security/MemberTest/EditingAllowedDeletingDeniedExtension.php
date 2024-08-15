<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class EditingAllowedDeletingDeniedExtension extends DataExtension implements TestOnly
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
