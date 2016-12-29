<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;

class PasswordValidatorTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     * @var bool
     */
    protected $usesDatabase = true;

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

    /**
     * Test that a certain number of historical passwords are checked if specified
     */
    public function testHistoricalPasswordCount()
    {
        $validator = new PasswordValidator;
        $validator->checkHistoricalPasswords(3);
        Member::set_password_validator($validator);

        $member = new Member;
        $member->FirstName = 'Repeat';
        $member->Surname = 'Password-Man';
        $member->Password = 'honk';
        $member->write();

        // Create a set of used passwords
        $member->changePassword('foobar');
        $member->changePassword('foobaz');
        $member->changePassword('barbaz');

        $this->assertFalse($member->changePassword('barbaz')->isValid());
        $this->assertFalse($member->changePassword('foobaz')->isValid());
        $this->assertFalse($member->changePassword('foobar')->isValid());
        $this->assertTrue($member->changePassword('honk')->isValid());
        $this->assertTrue($member->changePassword('newpassword')->isValid());
    }
}
