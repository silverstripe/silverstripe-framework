<?php

namespace SilverStripe\ORM\Tests\CascadeDeleteTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class RelatedObject extends DataObject implements TestOnly
{
    private static $table_name = 'CascadeDeleteTest_RelatedObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_to = [
        'Parent' => ChildObject::class,
    ];
}
