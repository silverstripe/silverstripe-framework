<?php

namespace SilverStripe\Security\Tests\Validation;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Tests\Validation\RulesPasswordValidatorTest\DummyPasswordValidator;

class PasswordValidatorTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     * @var bool
     */
    protected $usesDatabase = true;

    public function testValidate()
    {
        $validator = new DummyPasswordValidator;
        $validator->setHistoricCount(3);
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
