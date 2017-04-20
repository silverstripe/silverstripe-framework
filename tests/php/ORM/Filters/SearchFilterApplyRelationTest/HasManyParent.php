<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HasManyParent extends DataObject implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasManyParent';

    private static $db = array(
        "Title" => "Varchar"
    );
}
