<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class RelatedObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataExtensionTest_RelatedObject';

    private static $db = array(
        "FieldOne" => "Varchar",
        "FieldTwo" => "Varchar"
    );

    private static $has_one = array(
        "Contact" => TestMember::class
    );
}
