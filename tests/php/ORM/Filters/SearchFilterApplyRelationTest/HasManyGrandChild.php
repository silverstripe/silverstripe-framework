<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

class HasManyGrandChild extends HasManyChild
{
    private static $table_name = 'SearchFilterApplyRelationTest_HasManyGrandChild';

    private static $has_one = array(
        "SearchFilterApplyRelationTest_DO" => TestObject::class,
    );
}
