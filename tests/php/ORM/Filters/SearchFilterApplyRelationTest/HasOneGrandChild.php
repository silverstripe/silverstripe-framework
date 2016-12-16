<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class HasOneGrandChild extends HasOneChild implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasOneGrantChild';

    // This is to create an seperate Table only.
    private static $db = array(
        "GrantChildField" => "Varchar",
    );

    private static $has_many = array(
        "SearchFilterApplyRelationTest_DOs" => TestObject::class,
    );
}
