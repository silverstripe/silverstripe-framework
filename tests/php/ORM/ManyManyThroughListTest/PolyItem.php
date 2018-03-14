<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use Generator;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class PolyItem extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_PolyItem';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_many = [
        'JoinObject' => PolyJoinObject::class . '.Items',
    ];

    /**
     * Placeholder for missing belongs_many_many for polymorphic relation
     *
     * @todo Make this work for belongs_many_many
     * @return Generator|DataObject[]
     */
    public function Objects()
    {
        foreach ($this->JoinObject() as $object) {
            $objectParent = $object->Parent();
            if ($objectParent && $objectParent->exists()) {
                yield $objectParent;
            }
        }
    }
}
