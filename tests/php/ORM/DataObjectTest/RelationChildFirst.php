<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

class RelationChildFirst extends RelationParent
{
    private static $table_name = 'DataObjectTest_RelationChildFirst';

    private static $many_many = [
        'ManyNext' => RelationChildSecond::class,
    ];
}
