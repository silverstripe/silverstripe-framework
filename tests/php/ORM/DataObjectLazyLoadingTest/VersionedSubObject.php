<?php

namespace SilverStripe\ORM\Tests\DataObjectLazyLoadingTest;

use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class VersionedSubObject extends VersionedObject
{
    private static $table_name = 'VersionedLazySub_DataObject';

    private static $db = array(
        "ExtraField" => "Varchar",
    );
    private static $extensions = array(
        Versioned::class
    );
}
