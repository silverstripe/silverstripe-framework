<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * Alters stage mode of extended object to be public
 */
class PublicExtension extends DataExtension implements TestOnly
{
    public function canViewNonLive($member = null)
    {
        return true;
    }
}
