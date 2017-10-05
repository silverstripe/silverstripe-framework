<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;

/**
 * Extension that does something extra when changing a member's password
 */
class ExtendedChangePasswordExtension extends DataExtension implements TestOnly
{
    public function onBeforeChangePassword($newPassword, $valid)
    {
        $valid->addError('Extension failed to handle Mary changing her password');
    }
}
