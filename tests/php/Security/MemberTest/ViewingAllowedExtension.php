<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class ViewingAllowedExtension extends DataExtension implements TestOnly
{

    public function canView($member = null)
    {
        return true;
    }
}
