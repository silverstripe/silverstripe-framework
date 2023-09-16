<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\Queries\SQLSelect;

class DBFieldAddsToQuery extends DBText implements TestOnly
{
    public function addToQuery(&$query)
    {
        // Add a new item, to validate that tableName and name are set correctly.
        /** @var SQLSelect $query */
        $query->addSelect([$this->name . '2' => '"' . $this->tableName . '"."' . $this->name . '"']);
    }
}
