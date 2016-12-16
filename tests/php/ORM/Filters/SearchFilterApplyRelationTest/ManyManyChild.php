<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;

class ManyManyChild extends ManyManyParent implements TestOnly
{
    private static $table_name = 'SearchFilterApplyRelationTest_ManyManyChild';

    // This is to create an seperate Table only.
    private static $db = array(
        "ChildField" => "Varchar"
    );
}
