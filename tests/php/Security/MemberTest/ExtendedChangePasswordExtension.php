<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ValidationResult;

/**
 * Extension that does something extra when changing a member's password
 */
class ExtendedChangePasswordExtension extends Extension implements TestOnly
{
    protected function onBeforeChangePassword($newPassword, $valid)
    {
        $valid->addError('Extension failed to handle Mary changing her password');
    }
}
