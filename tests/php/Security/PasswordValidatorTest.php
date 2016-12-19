<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;

class PasswordValidatorTest extends SapphireTest
{

    public function testValidate()
    {
        $v = new PasswordValidator();
        $r = $v->validate('', new Member());
        $this->assertTrue($r->isValid(), 'Empty password is valid by default');

        $r = $v->validate('mypassword', new Member());
        $this->assertTrue($r->isValid(), 'Non-Empty password is valid by default');
    }

    public function testValidateMinLength()
    {
        $v = new PasswordValidator();

        $v->minLength(4);
        $r = $v->validate('123', new Member());
        $this->assertFalse($r->isValid(), 'Password too short');

        $v->minLength(4);
        $r = $v->validate('1234', new Member());
        $this->assertTrue($r->isValid(), 'Password long enough');
    }

    public function testValidateMinScore()
    {
        $v = new PasswordValidator();
        $v->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"));

        $r = $v->validate('aA', new Member());
        $this->assertFalse($r->isValid(), 'Passing too few tests');

        $r = $v->validate('aA1', new Member());
        $this->assertTrue($r->isValid(), 'Passing enough tests');
    }

    public function testHistoricalPasswordCount()
    {
        $this->markTestIncomplete();
    }
}
