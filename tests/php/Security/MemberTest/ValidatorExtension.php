<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class ValidatorExtension extends DataExtension implements TestOnly
{

    protected function updateValidator($validator)
    {
        $validator->addRequiredField('Surname');
        $validator->removeRequiredField('FirstName');
    }
}
