<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Security\PasswordValidator;

class TestPasswordValidator extends PasswordValidator
{
    public function __construct()
    {
        $this->minLength(7);
        $this->checkHistoricalPasswords(6);
        $this->characterStrength(3, array('lowercase', 'uppercase', 'digits', 'punctuation'));
    }
}
