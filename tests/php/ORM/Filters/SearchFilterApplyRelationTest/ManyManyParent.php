<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ManyManyParent extends DataObject implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_ManyManyParent';

    private static $db = array(
        "Title" => "Varchar"
    );
}
