<?php

namespace SilverStripe\Assets\Tests\AssetControlExtensionTest;

use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * A basic unversioned object
 *
 * @property string $Title
 * @property DBFile $Image
 */
class TestObject extends DataObject implements TestOnly
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Image' => "DBFile('image/supported')"
    );

    private static $table_name = 'AssetControlExtensionTest_TestObject';

    /**
     * @param Member $member
     * @return bool
     */
    public function canView($member = null)
    {
        return true;
    }
}
