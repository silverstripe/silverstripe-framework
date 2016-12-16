<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HasOneParent extends DataObject implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasOneParent';

    private static $db = array(
        "Title" => "Varchar"
    );
}
