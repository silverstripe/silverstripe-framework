<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

/**
 * @mixin Hierarchy
 */
class SortableHierarchyModel extends DataObject implements TestOnly
{
    private static $table_name = 'HierarchyTest_SortableHierarchyModel';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int'
    ];

    private static $extensions = [
        Hierarchy::class,
    ];

    private static $default_sort = 'Sort';

    private static $sort_field = 'Sort';
}
