<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBText;

class DBFieldAddsToQuery extends DBText implements TestOnly
{
    public function addToQuery(&$query)
    {
        $select = $query->getSelect();
        unset($select[$this->name]);
        $query->setSelect($select);
    }
}
