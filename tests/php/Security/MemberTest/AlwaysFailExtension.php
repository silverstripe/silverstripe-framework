<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * Extension that adds additional validation criteria
 */
class AlwaysFailExtension extends DataExtension implements TestOnly
{
    public function updatePHP($data, $form)
    {
        return false;
    }
}
