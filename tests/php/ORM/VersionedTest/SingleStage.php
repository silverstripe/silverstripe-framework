<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class SingleStage extends DataObject implements TestOnly
{
    private static $table_name = 'VersionedTest_SingleStage';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $extensions = array(
        'SilverStripe\\ORM\\Versioning\\Versioned("Versioned")'
    );
}
