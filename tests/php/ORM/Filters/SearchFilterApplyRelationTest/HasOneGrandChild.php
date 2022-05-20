<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class HasOneGrandChild extends HasOneChild implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasOneGrantChild';

    // This is to create a separate Table only.
    private static $db = [
        "GrantChildField" => "Varchar",
    ];

    private static $has_many = [
        "SearchFilterApplyRelationTest_DOs" => TestObject::class,
    ];
}
