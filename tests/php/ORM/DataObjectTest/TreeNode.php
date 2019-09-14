<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLUpdate;

/**
 * The purpose of this test class is to test recursive writes and make sure we don't get stuck in an infinite loop.
 * @property int $WriteCount Number of times this object was written sine the last call of `resetCount`
 */
class TreeNode extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_TreeNode';

    private static $db = [
        'Title' => 'Varchar',
        'WriteCount' => 'Int'
    ];

    private static $has_one = [
        'Parent' => self::class,
        'Cycle' => self::class,
    ];

    private static $has_many = [
        'Children' => self::class,
    ];

    public function write($showDebug = false, $forceInsert = false, $forceWrite = false, $writeComponents = false)
    {
        // Force the component to fetch its Parent and Cycle relation so we have components to recursively write
        $this->Parent;
        $this->Cycle;

        // Count a write attempts
        $this->WriteCount++;

        return parent::write($showDebug, $forceInsert, $forceWrite, $writeComponents);
    }

    /**
     * Reset the WriteCount on all TreeNodes
     */
    public function resetCounts()
    {
        $update = new SQLUpdate(
            sprintf('"%s"', self::baseTable()),
            ['"WriteCount"' => 0]
        );
        $results = $update->execute();
    }
}
