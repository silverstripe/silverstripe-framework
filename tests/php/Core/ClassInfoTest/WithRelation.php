<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

class WithRelation extends NoFields
{
    private static $table_name = 'ClassInfoTest_WithRelation';

    private static $has_one = [
        'Relation' => HasFields::class
    ];
}
