<?php

namespace SilverStripe\ORM\Tests\CascadeDeleteTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ParentObject extends DataObject implements TestOnly
{
    private static $table_name = 'CascadeDeleteTest_ParentObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $cascade_deletes = [
        'Children'
    ];

    private static $has_many = [
        'Children' => ChildObject::class,
    ];
}
