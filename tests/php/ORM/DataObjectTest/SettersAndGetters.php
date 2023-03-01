<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SettersAndGetters extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_SettersAndGetters';

    private static $db = [
        'MyTestField' => 'Varchar(255)',
    ];

    public function setMyTestField($val)
    {
        $this->setField('MyTestField', strtolower($val));
    }

    public function getMyTestField()
    {
        return strtoupper($this->getField('MyTestField'));
    }
}
