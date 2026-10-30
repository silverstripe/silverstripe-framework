<?php

namespace SilverStripe\Security\Tests\MemberAuthenticator\CookieAuthenticationHandlerTest;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Member;

/**
 * @extends Extension<Member>
 */
class VetoLoginExtension extends Extension implements TestOnly
{
    public const string VETOED_EMAIL = 'vetoed@example.com';

    protected function canLogIn(ValidationResult $result): void
    {
        if ($this->getOwner()->Email === VetoLoginExtension::VETOED_EMAIL) {
            $result->addError('Login vetoed');
        }
    }
}
