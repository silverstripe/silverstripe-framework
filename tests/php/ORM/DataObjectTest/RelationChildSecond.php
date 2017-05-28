<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

class RelationChildSecond extends RelationParent
{
    private static $table_name = 'DataObjectTest_RelationChildSecond';

    private static $belongs_many_many = [
        'ManyPrev' => RelationChildFirst::class,
    ];
}
