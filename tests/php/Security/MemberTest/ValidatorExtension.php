<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class ValidatorExtension extends Extension implements TestOnly
{

    protected function updateValidator($validator)
    {
        $validator->addRequiredField('Surname');
        $validator->removeRequiredField('FirstName');
    }
}
