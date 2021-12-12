<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class HasOneChild extends HasOneParent implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasOneChild';

    // This is to create a separate Table only.
    private static $db = [
        "ChildField" => "Varchar"
    ];
}
