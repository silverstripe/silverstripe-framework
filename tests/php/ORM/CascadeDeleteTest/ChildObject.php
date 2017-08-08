<?php

namespace SilverStripe\ORM\Tests\CascadeDeleteTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ParentObject Parent()
 * @method RelatedObject Related()
 * @method ManyManyList Children()
 */
class ChildObject extends DataObject implements TestOnly
{
    private static $table_name = 'CascadeDeleteTest_ChildObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $cascade_deletes = [
        'Children'
    ];

    private static $has_one = [
        'Parent' => ParentObject::class,
        'Related' => RelatedObject::class,
    ];

    private static $many_many = [
        'Children' => GrandChildObject::class,
    ];
}
