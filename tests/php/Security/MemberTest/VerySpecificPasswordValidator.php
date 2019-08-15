<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\PasswordValidator;

class VerySpecificPasswordValidator extends PasswordValidator implements TestOnly
{
    public function validate($password, $member)
    {
        $result = ValidationResult::create();
        if (strlen($password) !== 17) {
            $result->addError('Password must be 17 characters long');
        }
        return $result;
    }
}
