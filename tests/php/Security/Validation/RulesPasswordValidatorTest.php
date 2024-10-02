<?php

namespace SilverStripe\Security\Tests\Validation;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Validation\RulesPasswordValidator;

class RulesPasswordValidatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        RulesPasswordValidator::config()
            ->remove('min_length')
            ->remove('historic_count')
            ->set('min_test_score', 0);
    }

    public function testValidate()
    {
        $v = new RulesPasswordValidator();
        $r = $v->validate('', new Member());
        $this->assertTrue($r->isValid(), 'Empty password is valid by default');

        $r = $v->validate('mypassword', new Member());
        $this->assertTrue($r->isValid(), 'Non-Empty password is valid by default');
    }

    public function testValidateMinLength()
    {
        $v = new RulesPasswordValidator();

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
        $v = new RulesPasswordValidator();
        $v->setMinTestScore(3);
        $v->setTestNames(["lowercase", "uppercase", "digits", "punctuation"]);

        $r = $v->validate('aA', new Member());
        $this->assertFalse($r->isValid(), 'Passing too few tests');

        $r = $v->validate('aA1', new Member());
        $this->assertTrue($r->isValid(), 'Passing enough tests');

        // Ensure min score without tests works (uses default tests)
        $v = new RulesPasswordValidator();
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
}
