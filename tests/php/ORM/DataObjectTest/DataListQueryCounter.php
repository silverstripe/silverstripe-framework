<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\ORM\DataQuery;

/**
 * This is designed around the chunk method so we can count the number of queries run.
 */
class DataListQueryCounter extends DataQuery
{
    private $queryCount = 0;
    
    /**
     * When the DataList gets clone our reference to parent will be attached to our cloned DataListQueryCounter. So all
     * DataListQueryCounter::parent will point back to the original one that go created by with the constructor.
     * @var DataListQueryCounter
     */
    private $parent;

    public function __construct($dataClass)
    {
        parent::__construct($dataClass);
        $this->parent = $this;
    }

    public function getFinalisedQuery($queriedColumns = null)
    {
        $this->increment();
        return parent::getFinalisedQuery($queriedColumns);
    }

    private function increment()
    {
        $this->parent->queryCount++;
    }

    public function getCount()
    {
        return $this->parent->queryCount;
    }
}
