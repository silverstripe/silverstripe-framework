<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Validation\PasswordValidator;

class VerySpecificPasswordValidator extends PasswordValidator implements TestOnly
{
    public function validate(string $password, Member $member): ValidationResult
    {
        $result = ValidationResult::create();
        if (strlen($password ?? '') !== 17) {
            $result->addError('Password must be 17 characters long');
        }
        return $result;
    }
}
