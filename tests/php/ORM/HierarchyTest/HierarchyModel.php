<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

/**
 * @mixin Hierarchy
 */
class HierarchyModel extends DataObject implements TestOnly
{
    private static $table_name = 'HierarchyTest_HierarchyModel';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $extensions = [
        Hierarchy::class,
    ];
}
