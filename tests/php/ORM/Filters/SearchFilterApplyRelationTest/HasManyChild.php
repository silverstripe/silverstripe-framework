<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class HasManyChild extends HasManyParent implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasManyChild';

    // This is to create an separate Table only.
    private static $db = array(
        "ChildField" => "Varchar"
    );
}
