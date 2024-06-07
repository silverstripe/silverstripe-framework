<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ParentModel extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_ParentModel';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Child' => Child::class,
        'ChildPolymorphic' => DataObject::class,
    ];

    private static $has_many = [
        'ChildrenHasMany' => Child::class . '.Parent',
    ];

    private static $many_many = [
        'ChildrenManyMany' => Child::class,
        'ChildrenManyManyThrough' => [
            'through' => ParentChildJoin::class,
            'from' => 'Parent',
            'to' => 'Child',
        ]
    ];
}
