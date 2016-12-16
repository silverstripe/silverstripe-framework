<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class UnversionedWithField extends DataObject implements TestOnly
{
    private static $table_name = 'VersionedTest_UnversionedWithField';

    private static $db = [
        'Version' => 'Varchar(255)'
    ];
}
