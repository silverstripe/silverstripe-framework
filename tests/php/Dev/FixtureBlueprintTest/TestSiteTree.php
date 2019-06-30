<?php declare(strict_types = 1);

namespace SilverStripe\Dev\Tests\FixtureBlueprintTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestSiteTree extends DataObject implements TestOnly
{
    private static $table_name = 'FixtureBlueprintTest_TestSiteTree';

    private static $db = array(
        "Title" => "Varchar"
    );
}
