<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class ManyManyGrandChild extends ManyManyChild implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_ManyManyGrandChild';

    // This is to create an seperate Table only.
    private static $db = array(
        "GrantChildField" => "Varchar",
    );

    private static $belongs_many_many = array(
        "DOs" => TestObject::class,
    );
}
