<?php declare(strict_types = 1);

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Security\PasswordValidator;

class TestPasswordValidator extends PasswordValidator
{
    public function __construct()
    {
        $this->setMinLength(7);
        $this->setHistoricCount(6);
        $this->setMinTestScore(3);
        $this->setTestNames(['lowercase', 'uppercase', 'digits', 'punctuation']);
    }
}
