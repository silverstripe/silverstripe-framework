<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Public access is provided via extension rather than overriding canViewVersioned
 *
 * @mixin Versioned
 * @mixin PublicExtension
 */
class PublicViaExtension extends DataObject implements TestOnly
{

    private static $table_name = 'VersionedTest_PublicViaExtension';

    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return true;
    }

    private static $db = array(
        'Title' => 'Varchar'
    );

    private static $extensions = array(
        Versioned::class,
        PublicExtension::class,
    );
}
