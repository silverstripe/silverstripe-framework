<?php declare(strict_types = 1);

namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

class PasswordValidatorTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     * @var bool
     */
    protected $usesDatabase = true;

    protected function setUp()
    {
        parent::setUp();

        PasswordValidator::config()
            ->remove('min_length')
            ->remove('historic_count')
            ->set('min_test_score', 0);
    }

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

        $v->setMinLength(4);
        $r = $v->validate('123', new Member());
        $this->assertFalse($r->isValid(), 'Password too short');

        $v->setMinLength(4);
        $r = $v->validate('1234', new Member());
        $this->assertTrue($r->isValid(), 'Password long enough');
    }

    public function testValidateMinScore()
    {
        // Set both score and set of tests
        $v = new PasswordValidator();
        $v->setMinTestScore(3);
        $v->setTestNames(["lowercase", "uppercase", "digits", "punctuation"]);

        $r = $v->validate('aA', new Member());
        $this->assertFalse($r->isValid(), 'Passing too few tests');

        $r = $v->validate('aA1', new Member());
        $this->assertTrue($r->isValid(), 'Passing enough tests');

        // Ensure min score without tests works (uses default tests)
        $v = new PasswordValidator();
        $v->setMinTestScore(3);

        $r = $v->validate('aA', new Member());
        $this->assertFalse($r->isValid(), 'Passing too few tests');

        $r = $v->validate('aA1', new Member());
        $this->assertTrue($r->isValid(), 'Passing enough tests');

        // Ensure that min score is only triggered if there are any failing tests at all
        $v->setMinTestScore(1000);
        $r = $v->validate('aA1!', new Member());
        $this->assertTrue($r->isValid(), 'Passing enough tests');
    }

    /**
     * Test that a certain number of historical passwords are checked if specified
     */
    public function testHistoricalPasswordCount()
    {
        $validator = new PasswordValidator;
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
