<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

use SilverStripe\ORM\DataObject;

class ThroughObjectPolymorphic extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ThroughObjectPolymorphic';

    private static $has_one = [
        'Parent' => DataObject::class, // Will create a ParentID column + ParentColumn Enum column
        'NodeObj' => Node::class,
    ];
}
