<?php

namespace SilverStripe\Core\Tests\Validation;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Member_Validator;

class MemberValidatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testMemberValidator(): void
    {
        $member = new Member();
        $compositeValidator = $member->getCMSCompositeValidator();

        $memberValidators = $compositeValidator->getValidatorsByType(Member_Validator::class);
        $this->assertCount(1, $memberValidators, 'We expect exactly one member validator');
    }
}
