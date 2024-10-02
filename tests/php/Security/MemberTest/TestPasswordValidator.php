<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Validation\RulesPasswordValidator;

class TestPasswordValidator extends RulesPasswordValidator implements TestOnly
{
    public function __construct()
    {
        $this->setMinLength(7);
        $this->setHistoricCount(6);
        $this->setMinTestScore(3);
        $this->setTestNames(['lowercase', 'uppercase', 'digits', 'punctuation']);
    }
}
