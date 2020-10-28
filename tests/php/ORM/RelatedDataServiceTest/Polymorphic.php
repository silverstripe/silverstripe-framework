<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

use SilverStripe\ORM\DataObject;

class Polymorphic extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_Polymorphic';

    private static $has_one = [
        'Parent' => DataObject::class
    ];
}
